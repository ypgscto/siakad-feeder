<?php

namespace App\Services\Sync;

use App\DTOs\SyncBatchResult;
use App\Models\FeederSyncLog;
use App\Services\Feeder\FeederClient;
use App\Services\Feeder\FeederLookupService;
use App\Services\Feeder\FeederProdiMapService;
use App\Support\Feeder\FeederResponseParser;
use App\Support\Feeder\KelasNamaResolver;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class KelasFeederService
{
    public function __construct(
        protected FeederClient $feeder,
        protected FeederLookupService $lookup,
        protected FeederProdiMapService $prodiMaps,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $classes
     */
    public function insertKelasKuliah(array $classes, string $prodiId, string $tahunId): SyncBatchResult
    {
        $result = new SyncBatchResult;
        $feederProdiId = $this->prodiMaps->resolve($prodiId)['id_prodi'];

        foreach ($classes as $row) {
            $mkKode = (string) ($row['mk_kode'] ?? '');
            $namaKelas = KelasNamaResolver::forFeeder($row);
            $label = "{$mkKode} / {$namaKelas}";

            try {
                $idMatkul = $this->lookup->idMatkulByKode($mkKode);
                if ($idMatkul === null || $idMatkul === '') {
                    $this->fail($result, 'insert_kelas_kuliah', $label, 'id_matkul tidak ditemukan di Feeder.');

                    continue;
                }

                $response = $this->feeder->callXml('InsertKelasKuliah', [
                    'id_matkul' => $idMatkul,
                    'id_prodi' => $feederProdiId,
                    'id_semester' => $tahunId,
                    'nama_kelas_kuliah' => $namaKelas,
                    'bahasan' => '',
                    'tanggal_mulai_efektif' => '',
                    'tanggal_akhir_efektif' => '',
                ]);
            } catch (RuntimeException $e) {
                $this->fail($result, 'insert_kelas_kuliah', $label, $e->getMessage());

                continue;
            }

            if (! FeederResponseParser::isSuccess($response)) {
                $this->fail($result, 'insert_kelas_kuliah', $label, FeederResponseParser::errorDescription($response), $response);

                continue;
            }

            $result->successCount++;
            $result->successMessages[] = "Kelas {$label} OK";
            $this->logSuccess('insert_kelas_kuliah', $label);
        }

        return $result;
    }

    /**
     * @param  list<array<string, mixed>>  $participants
     */
    public function insertPesertaKelas(
        array $participants,
        string $tahunId,
        string $mkKode,
        string $namaKelas,
    ): SyncBatchResult {
        $result = new SyncBatchResult;
        $idKelas = $this->lookup->idKelasKuliah($tahunId, $mkKode, $namaKelas);

        if ($idKelas === null) {
            $result->recordFailure(
                "{$mkKode} / {$namaKelas}",
                'ID kelas kuliah tidak ditemukan di Feeder. Kirim kelas terlebih dahulu.',
            );

            return $result;
        }

        foreach ($participants as $row) {
            $nim = (string) ($row['nim'] ?? $row['mhsw_id'] ?? '');

            try {
                $idReg = $this->lookup->idRegistrasiMahasiswaByNim($nim);
                if ($idReg === null) {
                    $this->fail($result, 'insert_peserta_kelas', $nim, 'ID registrasi mahasiswa tidak ditemukan di Feeder.');

                    continue;
                }

                $response = $this->feeder->callXml('InsertPesertaKelasKuliah', [
                    'id_registrasi_mahasiswa' => $idReg,
                    'id_kelas_kuliah' => $idKelas,
                ]);
            } catch (RuntimeException $e) {
                $this->fail($result, 'insert_peserta_kelas', $nim, $e->getMessage());

                continue;
            }

            if (! FeederResponseParser::isSuccess($response)) {
                $this->fail($result, 'insert_peserta_kelas', $nim, FeederResponseParser::errorDescription($response), $response);

                continue;
            }

            $result->successCount++;
            $result->successMessages[] = "Peserta NIM {$nim} OK";
            $this->logSuccess('insert_peserta_kelas', $nim);
        }

        return $result;
    }

    public function insertDosenPengajar(
        string $tahunId,
        string $mkKode,
        string $namaKelas,
        string $nidn,
        string $sks = '2',
    ): SyncBatchResult {
        $result = new SyncBatchResult;
        $idKelas = $this->lookup->idKelasKuliah($tahunId, $mkKode, $namaKelas);
        $idRegDosen = $this->lookup->idRegistrasiDosenByNidn($nidn);

        if ($idKelas === null || $idRegDosen === null) {
            $result->recordFailure(
                "{$mkKode} / {$namaKelas} ({$nidn})",
                'ID kelas kuliah atau registrasi dosen tidak ditemukan di Feeder.',
            );

            return $result;
        }

        try {
            $response = $this->feeder->callXml('InsertDosenPengajarKelasKuliah', [
                'id_registrasi_dosen' => $idRegDosen,
                'id_kelas_kuliah' => $idKelas,
                'id_substansi' => '',
                'sks_substansi_total' => $sks,
                'rencana_minggu_pertemuan' => (string) config('feeder_maps.kelas.rencana_minggu', '16'),
                'rencana_tatap_muka' => (string) config('feeder_maps.kelas.rencana_tatap_muka', '16'),
                'realisasi_tatap_muka' => (string) config('feeder_maps.kelas.realisasi_tatap_muka', '16'),
                'id_jenis_evaluasi' => (string) config('feeder_maps.kelas.id_jenis_evaluasi', '1'),
            ]);
        } catch (RuntimeException $e) {
            $this->fail($result, 'insert_dosen_pengajar', $nidn, $e->getMessage());

            return $result;
        }

        if (! FeederResponseParser::isSuccess($response)) {
            $this->fail($result, 'insert_dosen_pengajar', $nidn, FeederResponseParser::errorDescription($response), $response);

            return $result;
        }

        $result->successCount++;
        $result->successMessages[] = "Dosen NIDN {$nidn} OK";
        $this->logSuccess('insert_dosen_pengajar', $nidn);

        return $result;
    }

    protected function fail(
        SyncBatchResult $result,
        string $syncType,
        string $key,
        string $message,
        ?array $feederResponse = null,
    ): void {
        $result->recordFailure($key, $message);

        FeederSyncLog::query()->create([
            'sync_type' => $syncType,
            'payload_summary' => ['key' => $key],
            'feeder_error_code' => isset($feederResponse['error_code']) ? (int) $feederResponse['error_code'] : null,
            'feeder_error_desc' => $message,
            'success' => false,
            'user_id' => Auth::id(),
        ]);
    }

    protected function logSuccess(string $syncType, string $key): void
    {
        FeederSyncLog::query()->create([
            'sync_type' => $syncType,
            'payload_summary' => ['key' => $key],
            'success' => true,
            'user_id' => Auth::id(),
        ]);
    }
}
