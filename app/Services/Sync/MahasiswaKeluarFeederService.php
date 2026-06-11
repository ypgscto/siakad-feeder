<?php

namespace App\Services\Sync;

use App\DTOs\SyncBatchResult;
use App\Models\FeederSyncLog;
use App\Models\FeederCodeMap;
use App\Services\Feeder\FeederClient;
use App\Services\Feeder\FeederCodeMapService;
use App\Services\Feeder\FeederLookupService;
use App\Support\Feeder\FeederResponseParser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class MahasiswaKeluarFeederService
{
    public function __construct(
        protected FeederClient $feeder,
        protected FeederLookupService $lookup,
        protected FeederCodeMapService $codeMaps,
    ) {}

    /**
     * InsertMahasiswaLulusDO — mahasiswa lulus / DO.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function insertMahasiswaLulusDo(array $rows): SyncBatchResult
    {
        $result = new SyncBatchResult;

        foreach ($rows as $row) {
            $nim = (string) ($row['nim'] ?? $row['mhsw_id'] ?? '');

            try {
                $idReg = $this->lookup->idRegistrasiMahasiswaByNim($nim);
                if ($idReg === null) {
                    $this->fail($result, $nim, 'ID registrasi mahasiswa tidak ditemukan di Feeder.');

                    continue;
                }

                $response = $this->feeder->callXml(
                    'InsertMahasiswaLulusDO',
                    $this->buildRecord($idReg, $row),
                );
            } catch (RuntimeException $e) {
                $this->fail($result, $nim, $e->getMessage());

                continue;
            }

            if (! FeederResponseParser::isSuccess($response)) {
                $this->fail($result, $nim, FeederResponseParser::errorDescription($response), $response);

                continue;
            }

            $result->successCount++;
            $result->successMessages[] = "NIM {$nim} OK";
            $this->logSuccess($nim);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, string>
     */
    protected function buildRecord(string $idRegistrasi, array $row): array
    {
        $statusLulusId = (string) ($row['status_lulus_id'] ?? '');

        return [
            'id_registrasi_mahasiswa' => $idRegistrasi,
            'id_jenis_keluar' => $this->resolveJenisKeluar($statusLulusId),
            'tanggal_keluar' => $this->formatTanggalKeluar($row['tanggal_keluar'] ?? null),
            'keterangan' => '',
            'nomor_sk_yudisium' => '',
            'ipk' => (string) ($row['ipk'] ?? '0'),
            'nomor_ijazah' => (string) ($row['nomor_ijazah'] ?? ''),
        ];
    }

    protected function resolveJenisKeluar(string $statusLulusId): string
    {
        $resolved = $this->codeMaps->resolve(
            FeederCodeMap::CATEGORY_JENIS_KELUAR,
            $statusLulusId,
            (string) config('feeder_maps.jenis_keluar.default', '1'),
        );

        return $resolved !== null && $resolved !== '' ? $resolved : '1';
    }

    protected function formatTanggalKeluar(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    protected function fail(
        SyncBatchResult $result,
        string $nim,
        string $message,
        ?array $feederResponse = null,
    ): void {
        $result->failedCount++;
        $result->errorCounts[$message] = ($result->errorCounts[$message] ?? 0) + 1;

        FeederSyncLog::query()->create([
            'sync_type' => 'mahasiswa_lulus_do',
            'payload_summary' => ['nim' => $nim],
            'feeder_error_code' => isset($feederResponse['error_code']) ? (int) $feederResponse['error_code'] : null,
            'feeder_error_desc' => $message,
            'success' => false,
            'user_id' => Auth::id(),
        ]);
    }

    protected function logSuccess(string $nim): void
    {
        FeederSyncLog::query()->create([
            'sync_type' => 'mahasiswa_lulus_do',
            'payload_summary' => ['nim' => $nim],
            'success' => true,
            'user_id' => Auth::id(),
        ]);
    }
}
