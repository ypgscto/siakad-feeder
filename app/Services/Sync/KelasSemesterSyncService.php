<?php

namespace App\Services\Sync;

use App\DTOs\SyncBatchResult;
use App\Services\SiakadApiService;
use App\Support\Feeder\KelasNamaResolver;

class KelasSemesterSyncService
{
    public function __construct(
        protected SiakadApiService $siakad,
        protected KelasFeederService $kelasFeeder,
    ) {}

    public function syncFull(string $prodiId, string $tahunId): SyncBatchResult
    {
        $classes = $this->siakad->fetchClasses(['tahun_id' => $tahunId]);
        if ($prodiId !== '') {
            $classes = array_values(array_filter(
                $classes,
                fn (array $r) => ($r['prodi_kode'] ?? '') === $prodiId,
            ));
        }

        $result = $this->kelasFeeder->insertKelasKuliah($classes, $prodiId, $tahunId);
        $nidnByLogin = $this->indexNidnByLogin($this->siakad->fetchLecturers());

        foreach ($classes as $row) {
            $mkKode = (string) ($row['mk_kode'] ?? '');
            $namaKelasSiakad = (string) ($row['nama_kelas'] ?? '');
            $kelasNamaFeeder = KelasNamaResolver::forFeeder($row);
            $jadwalId = (string) ($row['id'] ?? '');

            if ($jadwalId === '' || $mkKode === '' || $namaKelasSiakad === '') {
                continue;
            }

            $participants = $this->siakad->fetchClassParticipants([
                'jadwal_id' => $jadwalId,
                'tahun_id' => $tahunId,
                'prodi_id' => $prodiId,
                'mk_kode' => $mkKode,
                'nama_kelas' => $namaKelasSiakad,
            ]);

            $result = $this->merge($result, $this->kelasFeeder->insertPesertaKelas(
                $participants,
                $tahunId,
                $mkKode,
                $kelasNamaFeeder,
            ));

            $dosenLogin = (string) ($row['dosen_login'] ?? '');
            $nidn = $nidnByLogin[$dosenLogin] ?? '';
            if ($nidn !== '') {
                $result = $this->merge($result, $this->kelasFeeder->insertDosenPengajar(
                    $tahunId,
                    $mkKode,
                    $kelasNamaFeeder,
                    $nidn,
                    (string) config('feeder_maps.kelas.default_sks_pengajar', '2'),
                ));
            }
        }

        return $result;
    }

    /**
     * @param  list<array<string, mixed>>  $lecturers
     * @return array<string, string>
     */
    protected function indexNidnByLogin(array $lecturers): array
    {
        $map = [];
        foreach ($lecturers as $d) {
            $nidn = trim((string) ($d['nidn'] ?? ''));
            if ($nidn === '') {
                continue;
            }
            foreach (['id', 'siakad_id'] as $key) {
                $login = (string) ($d[$key] ?? '');
                if ($login !== '') {
                    $map[$login] = $nidn;
                }
            }
        }

        return $map;
    }

    protected function merge(SyncBatchResult $a, SyncBatchResult $b): SyncBatchResult
    {
        return new SyncBatchResult(
            $a->successCount + $b->successCount,
            $a->failedCount + $b->failedCount,
            array_merge($a->successMessages, $b->successMessages),
            array_merge($a->errorCounts, $b->errorCounts),
            array_merge($a->failedItems, $b->failedItems),
        );
    }
}
