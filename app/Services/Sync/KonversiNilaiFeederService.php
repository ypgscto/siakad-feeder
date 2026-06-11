<?php

namespace App\Services\Sync;

use App\DTOs\SyncBatchResult;
use App\Models\FeederSyncLog;
use App\Services\Feeder\FeederClient;
use App\Services\Feeder\FeederLookupService;
use App\Support\Feeder\FeederResponseParser;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class KonversiNilaiFeederService
{
    public function __construct(
        protected FeederClient $feeder,
        protected FeederLookupService $lookup,
    ) {}

    /**
     * InsertNilaiTransferPendidikanMahasiswa — nilai MK pindahan / RPL.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function insertNilaiTransfer(array $rows): SyncBatchResult
    {
        $result = new SyncBatchResult;

        foreach ($rows as $row) {
            $nim = (string) ($row['nim'] ?? $row['mhsw_id'] ?? '');
            $mkKode = trim((string) ($row['mk_kode'] ?? ''));
            $label = "{$nim} / {$mkKode}";

            try {
                $idReg = $this->lookup->idRegistrasiMahasiswaByNim($nim);
                if ($idReg === null) {
                    $this->fail($result, $label, 'ID registrasi mahasiswa tidak ditemukan di Feeder.');

                    continue;
                }

                $idMatkul = $this->lookup->idMatkulByKode($mkKode);
                if ($idMatkul === null || $idMatkul === '') {
                    $this->fail($result, $label, 'id_matkul tidak ditemukan di Feeder.');

                    continue;
                }

                $response = $this->feeder->callXml(
                    'InsertNilaiTransferPendidikanMahasiswa',
                    $this->buildRecord($idReg, $idMatkul, $row),
                );
            } catch (RuntimeException $e) {
                $this->fail($result, $label, $e->getMessage());

                continue;
            }

            if (! FeederResponseParser::isSuccess($response)) {
                $this->fail($result, $label, FeederResponseParser::errorDescription($response), $response);

                continue;
            }

            $result->successCount++;
            $result->successMessages[] = "MK {$mkKode} NIM {$nim} OK";
            $this->logSuccess($nim, $mkKode);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, string>
     */
    protected function buildRecord(string $idRegistrasi, string $idMatkul, array $row): array
    {
        $mkKode = trim((string) ($row['mk_kode'] ?? ''));
        $sks = (string) ($row['sks_mk'] ?? '0');
        $nilaiHuruf = (string) ($row['nilai_huruf'] ?? '');
        $nilaiAngka = (string) ($row['bobot'] ?? $row['nilai_angka'] ?? '0');
        $idSemester = (string) ($row['tahun_masuk'] ?? $row['angkatan'] ?? '');

        return [
            'id_registrasi_mahasiswa' => $idRegistrasi,
            'id_matkul' => $idMatkul,
            'kode_mata_kuliah_asal' => $mkKode,
            'nama_mata_kuliah_asal' => (string) ($row['nama_mk'] ?? ''),
            'id_semester' => $idSemester,
            'sks_mata_kuliah_asal' => $sks,
            'sks_mata_kuliah_diakui' => $sks,
            'nilai_huruf_asal' => $nilaiHuruf,
            'nilai_huruf_diakui' => $nilaiHuruf,
            'nilai_angka_diakui' => $nilaiAngka,
        ];
    }

    protected function fail(
        SyncBatchResult $result,
        string $label,
        string $message,
        ?array $feederResponse = null,
    ): void {
        $result->recordFailure($label, $message);

        FeederSyncLog::query()->create([
            'sync_type' => 'nilai_konversi',
            'payload_summary' => ['label' => $label],
            'feeder_error_code' => isset($feederResponse['error_code']) ? (int) $feederResponse['error_code'] : null,
            'feeder_error_desc' => $message,
            'success' => false,
            'user_id' => Auth::id(),
        ]);
    }

    protected function logSuccess(string $nim, string $mkKode): void
    {
        FeederSyncLog::query()->create([
            'sync_type' => 'nilai_konversi',
            'payload_summary' => ['nim' => $nim, 'mk_kode' => $mkKode],
            'success' => true,
            'user_id' => Auth::id(),
        ]);
    }
}
