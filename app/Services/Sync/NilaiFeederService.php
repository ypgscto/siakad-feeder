<?php

namespace App\Services\Sync;

use App\DTOs\SyncBatchResult;
use App\Models\FeederSyncLog;
use App\Services\Feeder\FeederClient;
use App\Services\Feeder\FeederLookupService;
use App\Support\Feeder\FeederResponseParser;
use App\Support\Feeder\NilaiIndeksCalculator;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class NilaiFeederService
{
    public function __construct(
        protected FeederClient $feeder,
        protected FeederLookupService $lookup,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $participants
     */
    public function updateNilaiKelas(
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
                'ID kelas kuliah tidak ditemukan di Feeder.',
            );

            return $result;
        }

        foreach ($participants as $row) {
            $nim = (string) ($row['nim'] ?? $row['mhsw_id'] ?? '');
            $nilaiAngka = (float) ($row['nilai_angka'] ?? 0);
            $nilaiHuruf = (string) ($row['nilai_huruf'] ?? '');
            $nilaiIndeks = NilaiIndeksCalculator::fromAngka($nilaiAngka);

            try {
                $idReg = $this->lookup->idRegistrasiMahasiswaByNim($nim);
                if ($idReg === null) {
                    $this->fail($result, $nim, 'ID registrasi mahasiswa tidak ditemukan di Feeder.');

                    continue;
                }

                $response = $this->feeder->callXmlBody([
                    'act' => 'UpdateNilaiPerkuliahanKelas',
                    'key' => [
                        'id_kelas_kuliah' => $idKelas,
                        'id_registrasi_mahasiswa' => $idReg,
                    ],
                    'record' => [
                        'nilai_angka' => (string) $nilaiAngka,
                        'nilai_huruf' => $nilaiHuruf,
                        'nilai_indeks' => $nilaiIndeks ?? '0.00',
                    ],
                ]);
            } catch (RuntimeException $e) {
                $this->fail($result, $nim, $e->getMessage());

                continue;
            }

            if (! FeederResponseParser::isSuccess($response)) {
                $this->fail($result, $nim, FeederResponseParser::errorDescription($response), $response);

                continue;
            }

            $result->successCount++;
            $result->successMessages[] = "Nilai NIM {$nim} OK";
            $this->logSuccess($nim);
        }

        return $result;
    }

    protected function fail(
        SyncBatchResult $result,
        string $nim,
        string $message,
        ?array $feederResponse = null,
    ): void {
        $result->recordFailure($nim, $message);

        FeederSyncLog::query()->create([
            'sync_type' => 'update_nilai_kelas',
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
            'sync_type' => 'update_nilai_kelas',
            'payload_summary' => ['nim' => $nim],
            'success' => true,
            'user_id' => Auth::id(),
        ]);
    }
}
