<?php

namespace App\Services\Sync;

use App\DTOs\SyncBatchResult;
use App\Models\FeederSyncLog;
use App\Models\FeederCodeMap;
use App\Services\Feeder\FeederClient;
use App\Services\Feeder\FeederCodeMapService;
use App\Services\Feeder\FeederLookupService;
use App\Support\Feeder\FeederResponseParser;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class PerkuliahanFeederService
{
    public function __construct(
        protected FeederClient $feeder,
        protected FeederLookupService $lookup,
        protected FeederCodeMapService $codeMaps,
    ) {}

    /**
     * InsertPerkuliahanMahasiswa — IP semester, IPK, SKS per semester.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function insertPerkuliahanMahasiswa(array $rows): SyncBatchResult
    {
        $result = new SyncBatchResult;

        foreach ($rows as $row) {
            $nim = (string) ($row['nim'] ?? $row['mhsw_id'] ?? '');
            $tahunId = (string) ($row['tahun_id'] ?? '');

            try {
                $idReg = $this->lookup->idRegistrasiMahasiswaByNim($nim);
                if ($idReg === null) {
                    $this->fail($result, $nim, $tahunId, 'ID registrasi mahasiswa tidak ditemukan di Feeder.');

                    continue;
                }

                $response = $this->feeder->callXml('InsertPerkuliahanMahasiswa', $this->buildRecord($idReg, $tahunId, $row));
            } catch (RuntimeException $e) {
                $this->fail($result, $nim, $tahunId, $e->getMessage());

                continue;
            }

            if (! FeederResponseParser::isSuccess($response)) {
                $this->fail($result, $nim, $tahunId, FeederResponseParser::errorDescription($response), $response);

                continue;
            }

            $result->successCount++;
            $result->successMessages[] = "NIM {$nim} ({$tahunId}) OK";
            $this->logSuccess($nim, $tahunId);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, string>
     */
    protected function buildRecord(string $idRegistrasi, string $tahunId, array $row): array
    {
        return [
            'id_registrasi_mahasiswa' => $idRegistrasi,
            'id_semester' => $tahunId,
            'id_status_mahasiswa' => (string) ($this->codeMaps->resolve(
                FeederCodeMap::CATEGORY_STATUS_MAHASISWA,
                (string) ($row['status_mhsw_id'] ?? 'A'),
                (string) config('feeder_maps.perkuliahan.id_status_mahasiswa', 'A '),
            ) ?? 'A '),
            'ips' => (string) ($row['ip_semester'] ?? '0'),
            'ipk' => (string) ($row['ipk'] ?? '0'),
            'sks_semester' => (string) ($row['sks_semester'] ?? '0'),
            'total_sks' => (string) ($row['total_sks'] ?? '0'),
            'biaya_kuliah_smt' => (string) config('feeder_maps.perkuliahan.biaya_kuliah_smt', '0'),
            'id_pembiayaan' => (string) config('feeder_maps.id_pembiayaan', '1'),
        ];
    }

    protected function fail(
        SyncBatchResult $result,
        string $nim,
        string $tahunId,
        string $message,
        ?array $feederResponse = null,
    ): void {
        $result->recordFailure($nim, $message);

        FeederSyncLog::query()->create([
            'sync_type' => 'perkuliahan_mahasiswa',
            'payload_summary' => ['nim' => $nim, 'tahun_id' => $tahunId],
            'feeder_error_code' => isset($feederResponse['error_code']) ? (int) $feederResponse['error_code'] : null,
            'feeder_error_desc' => $message,
            'success' => false,
            'user_id' => Auth::id(),
        ]);
    }

    protected function logSuccess(string $nim, string $tahunId): void
    {
        FeederSyncLog::query()->create([
            'sync_type' => 'perkuliahan_mahasiswa',
            'payload_summary' => ['nim' => $nim, 'tahun_id' => $tahunId],
            'success' => true,
            'user_id' => Auth::id(),
        ]);
    }
}
