<?php

namespace App\Services\Sync;

use App\DTOs\SyncBatchResult;
use App\Models\FeederSyncLog;
use App\Services\Feeder\FeederClient;
use App\Services\Feeder\FeederCodeMapService;
use App\Services\Feeder\FeederProdiMapService;
use App\Models\FeederCodeMap;
use App\Support\Feeder\FeederResponseParser;
use App\Support\Feeder\HandphoneNormalizer;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class MahasiswaFeederService
{
    public function __construct(
        protected FeederClient $feeder,
        protected FeederProdiMapService $prodiMaps,
        protected FeederCodeMapService $codeMaps,
    ) {}

    /**
     * InsertBiodataMahasiswa + InsertRiwayatPendidikanMahasiswa.
     *
     * @param  list<array<string, mixed>>  $students
     */
    public function sendBiodataAndRiwayat(array $students, string $statusAwalId): SyncBatchResult
    {
        $result = new SyncBatchResult;

        foreach ($students as $student) {
            $nim = $this->nim($student);
            $nik = (string) ($student['nik'] ?? '');

            try {
                $handphone = $this->resolveHandphone($student);
            } catch (RuntimeException $e) {
                $this->fail($result, $nim, 'InsertBiodataMahasiswa', $e->getMessage());

                continue;
            }

            $idMahasiswa = $this->findIdMahasiswaByNik($nik);

            if ($idMahasiswa === null) {
                try {
                    $biodata = $this->feeder->callXml(
                        'InsertBiodataMahasiswa',
                        $this->buildBiodataRecord($student, $handphone),
                    );
                } catch (RuntimeException $e) {
                    $this->fail($result, $nim, 'InsertBiodataMahasiswa', $this->formatBiodataError($e->getMessage(), $handphone), handphone: $handphone);

                    continue;
                }

                if (! FeederResponseParser::isSuccess($biodata)) {
                    $message = FeederResponseParser::errorDescription($biodata);
                    $this->fail($result, $nim, 'InsertBiodataMahasiswa', $this->formatBiodataError($message, $handphone), $biodata, $handphone);

                    continue;
                }

                $idMahasiswa = (string) ($biodata['data']['id_mahasiswa'] ?? '');
                if ($idMahasiswa === '') {
                    $this->fail($result, $nim, 'InsertBiodataMahasiswa', 'id_mahasiswa tidak ada di respons Feeder.', $biodata, $handphone);

                    continue;
                }
            }

            try {
                $riwayatRecord = $this->buildRiwayatRecord($student, $statusAwalId, $idMahasiswa);
                $riwayat = $this->feeder->callXml('InsertRiwayatPendidikanMahasiswa', $riwayatRecord);
            } catch (RuntimeException $e) {
                $this->fail($result, $nim, 'InsertRiwayatPendidikanMahasiswa', $e->getMessage());

                continue;
            }

            if (! FeederResponseParser::isSuccess($riwayat)) {
                $this->fail($result, $nim, 'InsertRiwayatPendidikanMahasiswa', FeederResponseParser::errorDescription($riwayat), $riwayat);

                continue;
            }

            $result->successCount++;
            $result->successMessages[] = "NIM {$nim}: biodata + riwayat OK";
            $this->logSuccess('mahasiswa_biodata_riwayat', $nim, $statusAwalId);
        }

        return $result;
    }

    /**
     * InsertRiwayatPendidikanMahasiswa untuk mahasiswa yang sudah punya biodata di Feeder.
     *
     * @param  list<array<string, mixed>>  $students
     */
    public function sendRiwayatOnly(array $students, string $statusAwalId): SyncBatchResult
    {
        $result = new SyncBatchResult;

        foreach ($students as $student) {
            $nim = $this->nim($student);
            $nik = (string) ($student['nik'] ?? '');
            $idMahasiswa = $this->findIdMahasiswaByNik($nik);

            if ($idMahasiswa === null) {
                $this->fail($result, $nim, 'InsertRiwayatPendidikanMahasiswa', 'Biodata mahasiswa belum ada di Feeder (NIK tidak ditemukan).');

                continue;
            }

            try {
                $record = $this->buildRiwayatRecord($student, $statusAwalId, $idMahasiswa);
                $response = $this->feeder->callXml('InsertRiwayatPendidikanMahasiswa', $record);
            } catch (RuntimeException $e) {
                $this->fail($result, $nim, 'InsertRiwayatPendidikanMahasiswa', $e->getMessage());

                continue;
            }

            if (! FeederResponseParser::isSuccess($response)) {
                $this->fail($result, $nim, 'InsertRiwayatPendidikanMahasiswa', FeederResponseParser::errorDescription($response), $response);

                continue;
            }

            $result->successCount++;
            $result->successMessages[] = "NIM {$nim}: riwayat OK";
            $this->logSuccess('mahasiswa_riwayat', $nim, $statusAwalId);
        }

        return $result;
    }

    /**
     * UpdateRiwayatPendidikanMahasiswa.
     *
     * @param  list<array<string, mixed>>  $students
     */
    public function updateRiwayat(array $students, string $statusAwalId): SyncBatchResult
    {
        $result = new SyncBatchResult;

        foreach ($students as $student) {
            $nim = $this->nim($student);
            $idRegistrasi = $this->findIdRegistrasiMahasiswa($nim);

            if ($idRegistrasi === null) {
                $this->fail($result, $nim, 'UpdateRiwayatPendidikanMahasiswa', 'ID registrasi mahasiswa tidak ditemukan di Feeder.');

                continue;
            }

            try {
                $record = $this->buildRiwayatRecord($student, $statusAwalId, null, forUpdate: true);
                $response = $this->feeder->callXmlBody([
                    'act' => 'UpdateRiwayatPendidikanMahasiswa',
                    'key' => ['id_registrasi_mahasiswa' => $idRegistrasi],
                    'record' => $record,
                ]);
            } catch (RuntimeException $e) {
                $this->fail($result, $nim, 'UpdateRiwayatPendidikanMahasiswa', $e->getMessage());

                continue;
            }

            if (! FeederResponseParser::isSuccess($response)) {
                $this->fail($result, $nim, 'UpdateRiwayatPendidikanMahasiswa', FeederResponseParser::errorDescription($response), $response);

                continue;
            }

            $result->successCount++;
            $result->successMessages[] = "NIM {$nim}: update riwayat OK";
            $this->logSuccess('mahasiswa_riwayat_update', $nim, $statusAwalId);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $student
     * @return array<string, mixed>
     */
    public function buildBiodataRecord(array $student, ?string $handphone = null): array
    {
        $agamaNama = (string) ($student['agama_nama'] ?? '');
        $idAgama = $this->codeMaps->resolve(FeederCodeMap::CATEGORY_AGAMA, $agamaNama, '0') ?? '0';

        return [
            'nama_mahasiswa' => (string) ($student['nama'] ?? ''),
            'tempat_lahir' => (string) ($student['tempat_lahir'] ?? ''),
            'jenis_kelamin' => (string) ($student['jenis_kelamin_feeder'] ?? 'L'),
            'nama_ibu_kandung' => (string) ($student['nama_ibu_kandung'] ?? ''),
            'tanggal_lahir' => (string) ($student['tanggal_lahir'] ?? ''),
            'id_agama' => $idAgama,
            'kewarganegaraan' => config('feeder_maps.default_kewarganegaraan', 'ID'),
            'nik' => (string) ($student['nik'] ?? ''),
            'id_wilayah' => config('feeder.default_id_wilayah', '070000'),
            'nisn' => (string) ($student['nisn_placeholder'] ?? config('feeder_maps.default_nisn')),
            'kelurahan' => config('feeder_maps.default_kelurahan'),
            'email' => (string) ($student['email'] ?? '') ?: config('feeder_maps.default_email'),
            'jalan' => (string) ($student['alamat'] ?? ''),
            'kecamatan' => config('feeder_maps.default_kecamatan'),
            'penerima_kps' => '0',
            'id_kebutuhan_khusus_mahasiswa' => '2',
            'handphone' => $handphone ?? $this->resolveHandphone($student),
        ];
    }

    /**
     * @param  array<string, mixed>  $student
     */
    protected function resolveHandphone(array $student): string
    {
        $nik = (string) ($student['nik'] ?? '');
        $raw = (string) ($student['handphone'] ?? $student['telepon'] ?? '');

        foreach (HandphoneNormalizer::candidates($raw, $this->nim($student)) as $phone) {
            if (! $this->isHandphoneUsedByOther($phone, $nik)) {
                return $phone;
            }
        }

        throw new RuntimeException(
            'Nomor HP alternatif untuk NIM '.$this->nim($student).' sudah dipakai mahasiswa lain di Feeder.',
        );
    }

    protected function isHandphoneUsedByOther(string $handphone, string $nik): bool
    {
        $escaped = str_replace("'", '', $handphone);
        $rows = $this->feeder->getList('GetBiodataMahasiswa', "handphone = '{$escaped}'", 5);

        if ($rows === []) {
            return false;
        }

        if ($nik === '') {
            return true;
        }

        foreach ($rows as $row) {
            $existingNik = (string) ($row['nik'] ?? '');
            if ($existingNik !== '' && $existingNik !== $nik) {
                return true;
            }
        }

        return false;
    }

    protected function formatBiodataError(string $message, string $handphone): string
    {
        return $message.' [HP dikirim: '.$handphone.']';
    }

    /**
     * @param  array<string, mixed>  $student
     * @return array<string, mixed>
     */
    public function buildRiwayatRecord(
        array $student,
        string $statusAwalId,
        ?string $idMahasiswa = null,
        bool $forUpdate = false,
    ): array {
        $prodiMap = $this->prodiMaps->resolve((string) ($student['prodi_id'] ?? ''));
        $jenisDaftar = $this->resolveJenisDaftar($statusAwalId);
        $idProdi = $prodiMap['id_prodi'];
        $idProdiAsal = match ($jenisDaftar) {
            '2' => (string) ($prodiMap['prodi_asal'] ?? $idProdi),
            '16' => (string) ($prodiMap['prodi_rpl'] ?? $idProdi),
            default => $idProdi,
        };
        $idPtAsal = match ($jenisDaftar) {
            '2' => config('feeder_maps.id_perguruan_tinggi_pindahan'),
            '16' => config('feeder_maps.id_perguruan_tinggi_rpl'),
            default => config('feeder_maps.id_perguruan_tinggi'),
        };

        $tahunId = (string) ($student['tahun_id'] ?? '');
        $tanggalDaftar = substr($tahunId, 0, 4).'-09-01';

        $record = [
            'id_jalur_daftar' => config('feeder_maps.id_jalur_daftar'),
            'id_jenis_daftar' => $jenisDaftar,
            'id_periode_masuk' => $tahunId,
            'tanggal_daftar' => $tanggalDaftar,
            'id_perguruan_tinggi' => config('feeder_maps.id_perguruan_tinggi'),
            'id_prodi' => $idProdi,
            'biaya_masuk' => config('feeder_maps.biaya_masuk'),
            'id_pembiayaan' => config('feeder_maps.id_pembiayaan'),
            'id_perguruan_tinggi_asal' => $idPtAsal,
            'id_prodi_asal' => $idProdiAsal,
        ];

        if (! $forUpdate) {
            $record['nim'] = $this->nim($student);
            if ($idMahasiswa !== null && $idMahasiswa !== '') {
                $record['id_mahasiswa'] = $idMahasiswa;
            }
        }

        return $record;
    }

    protected function resolveJenisDaftar(string $statusAwalId): string
    {
        return $this->codeMaps->resolveOrFail(FeederCodeMap::CATEGORY_JENIS_DAFTAR, $statusAwalId);
    }

    protected function findIdMahasiswaByNik(string $nik): ?string
    {
        if ($nik === '') {
            return null;
        }

        $rows = $this->feeder->getList('GetBiodataMahasiswa', "nik = '".str_replace("'", '', $nik)."'");

        return isset($rows[0]['id_mahasiswa']) ? (string) $rows[0]['id_mahasiswa'] : null;
    }

    protected function findIdRegistrasiMahasiswa(string $nim): ?string
    {
        if ($nim === '') {
            return null;
        }

        $rows = $this->feeder->getList('GetListMahasiswa', "nim = '".str_replace("'", '', $nim)."'");

        return isset($rows[0]['id_registrasi_mahasiswa'])
            ? (string) $rows[0]['id_registrasi_mahasiswa']
            : null;
    }

    /**
     * @param  array<string, mixed>  $student
     */
    protected function nim(array $student): string
    {
        return (string) ($student['nim'] ?? $student['mhsw_id'] ?? '');
    }

    protected function fail(
        SyncBatchResult $result,
        string $nim,
        string $syncType,
        string $message,
        ?array $feederResponse = null,
        ?string $handphone = null,
    ): void {
        $result->failedCount++;
        $result->errorCounts[$message] = ($result->errorCounts[$message] ?? 0) + 1;

        $payload = ['nim' => $nim];
        if ($handphone !== null && $handphone !== '') {
            $payload['handphone'] = $handphone;
        }

        FeederSyncLog::query()->create([
            'sync_type' => $syncType,
            'payload_summary' => $payload,
            'feeder_error_code' => isset($feederResponse['error_code']) ? (int) $feederResponse['error_code'] : null,
            'feeder_error_desc' => $message,
            'success' => false,
            'user_id' => Auth::id(),
        ]);
    }

    protected function logSuccess(string $syncType, string $nim, string $statusAwalId): void
    {
        FeederSyncLog::query()->create([
            'sync_type' => $syncType,
            'payload_summary' => [
                'nim' => $nim,
                'status_awal_id' => $statusAwalId,
            ],
            'success' => true,
            'user_id' => Auth::id(),
        ]);
    }
}
