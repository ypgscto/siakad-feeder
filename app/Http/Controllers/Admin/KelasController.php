<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\SyncBatchResult;
use App\Http\Controllers\Admin\Concerns\LoadsAcademicMaster;
use App\Http\Controllers\Controller;
use App\Services\SiakadApiService;
use App\Services\Sync\KelasFeederService;
use App\Services\Sync\KelasSemesterSyncService;
use App\Support\Feeder\KelasNamaResolver;
use App\Support\Sync\SyncFlash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class KelasController extends Controller
{
    use LoadsAcademicMaster;

    public function index(Request $request, SiakadApiService $siakad): View
    {
        $master = $this->fetchProdiTahunMaster($siakad);
        $filters = $this->resolveProdiTahunFilters($request, $master);
        $classes = [];
        $error = $master['error'];
        $loaded = $request->boolean('load');

        if ($loaded && $error === null) {
            try {
                $classes = $siakad->fetchClasses(array_filter([
                    'tahun_id' => $filters['tahun_id'],
                ]));
                if ($filters['prodi_id'] !== '') {
                    $classes = array_values(array_filter(
                        $classes,
                        fn (array $r) => ($r['prodi_kode'] ?? '') === $filters['prodi_id'],
                    ));
                }
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        return view('admin.kelas.index', [
            'title' => 'Kelas Perkuliahan',
            'filters' => $filters,
            'master' => $master,
            'classes' => $classes,
            'error' => $error,
            'loaded' => $loaded,
        ]);
    }

    public function peserta(Request $request, SiakadApiService $siakad): View
    {
        $master = $this->fetchProdiTahunMaster($siakad);
        $filters = $this->resolveProdiTahunFilters($request, $master);
        $jadwalId = $request->string('jadwal_id')->toString();
        $mkKode = $request->string('mk_kode')->toString();
        $namaKelas = $request->string('nama_kelas')->toString();
        $kelasNama = $request->string('kelas_nama')->toString();
        $dosenLogin = $request->string('dosen_login')->toString();

        $participants = [];
        $lecturers = [];
        $error = $master['error'];

        if ($jadwalId !== '' && $error === null) {
            try {
                $participants = $siakad->fetchClassParticipants([
                    'jadwal_id' => $jadwalId,
                    'tahun_id' => $filters['tahun_id'],
                    'prodi_id' => $filters['prodi_id'],
                    'mk_kode' => $mkKode,
                    'nama_kelas' => $namaKelas,
                ]);
                $lecturers = $siakad->fetchLecturers();
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        $nidn = '';
        if ($dosenLogin !== '') {
            foreach ($lecturers as $d) {
                if (($d['id'] ?? '') === $dosenLogin || ($d['siakad_id'] ?? '') === $dosenLogin) {
                    $nidn = (string) ($d['nidn'] ?? '');
                    break;
                }
            }
        }

        return view('admin.kelas.peserta', [
            'title' => 'Peserta Kelas',
            'filters' => $filters,
            'master' => $master,
            'jadwalId' => $jadwalId,
            'mkKode' => $mkKode,
            'namaKelas' => $namaKelas,
            'kelasNama' => KelasNamaResolver::fromRequest($kelasNama, $namaKelas),
            'dosenLogin' => $dosenLogin,
            'nidn' => $nidn,
            'participants' => $participants,
            'error' => $error,
        ]);
    }

    public function sendKelasFull(Request $request, SiakadApiService $siakad, KelasSemesterSyncService $sync): RedirectResponse
    {
        @set_time_limit(900);
        $master = $this->fetchProdiTahunMaster($siakad);
        $filters = $this->resolveProdiTahunFilters($request, $master);

        if ($filters['prodi_id'] === '') {
            return redirect()
                ->route('admin.kelas.index', array_merge($filters, ['load' => 1]))
                ->with('error', 'Pilih program studi terlebih dahulu.');
        }

        return $this->redirectSync(
            $sync->syncFull($filters['prodi_id'], $filters['tahun_id']),
            'Kirim kelas + peserta + dosen',
            $filters,
        );
    }

    public function sendKelas(Request $request, SiakadApiService $siakad, KelasFeederService $sync): RedirectResponse
    {
        @set_time_limit(900);
        $master = $this->fetchProdiTahunMaster($siakad);
        $filters = $this->resolveProdiTahunFilters($request, $master);
        $classes = $this->loadClasses($siakad, $filters);
        $classes = $this->filterClasses($classes, $request);

        return $this->redirectSync(
            $sync->insertKelasKuliah($classes, $filters['prodi_id'], $filters['tahun_id']),
            'Kirim kelas kuliah',
            $filters,
        );
    }

    public function sendPeserta(Request $request, SiakadApiService $siakad, KelasFeederService $sync): RedirectResponse
    {
        @set_time_limit(900);
        $validated = $request->validate([
            'prodi_id' => ['required', 'string'],
            'tahun_id' => ['required', 'string'],
            'jadwal_id' => ['required', 'string'],
            'mk_kode' => ['required', 'string'],
            'nama_kelas' => ['required', 'string'],
            'kelas_nama' => ['nullable', 'string'],
            'nidn' => ['nullable', 'string'],
            'only_selected' => ['nullable', 'boolean'],
            'nims' => ['nullable', 'array'],
        ]);

        $validated['prodi_id'] = $this->enforceProdiOnFilters([
            'prodi_id' => (string) $validated['prodi_id'],
        ])['prodi_id'];

        $participants = $siakad->fetchClassParticipants([
            'jadwal_id' => $validated['jadwal_id'],
            'tahun_id' => $validated['tahun_id'],
            'prodi_id' => $validated['prodi_id'],
            'mk_kode' => $validated['mk_kode'],
            'nama_kelas' => $validated['nama_kelas'],
        ]);
        $participants = $this->filterParticipants($participants, $request);
        $kelasNamaFeeder = KelasNamaResolver::fromRequest(
            (string) ($validated['kelas_nama'] ?? ''),
            $validated['nama_kelas'],
        );

        $result = $sync->insertPesertaKelas(
            $participants,
            $validated['tahun_id'],
            $validated['mk_kode'],
            $kelasNamaFeeder,
        );

        $nidn = trim((string) ($validated['nidn'] ?? ''));
        if ($nidn !== '') {
            $dosenResult = $sync->insertDosenPengajar(
                $validated['tahun_id'],
                $validated['mk_kode'],
                $kelasNamaFeeder,
                $nidn,
                (string) config('feeder_maps.kelas.default_sks_pengajar', '2'),
            );
            $result = $this->mergeResults($result, $dosenResult);
        }

        return $this->redirectSync($result, 'Kirim peserta kelas', [
            'prodi_id' => $validated['prodi_id'],
            'tahun_id' => $validated['tahun_id'],
        ], 'admin.kelas.peserta', array_merge($validated, ['load' => 1]));
    }

    /**
     * @param  array{prodi_id: string, tahun_id: string}  $filters
     * @return list<array<string, mixed>>
     */
    protected function loadClasses(SiakadApiService $siakad, array $filters): array
    {
        $classes = $siakad->fetchClasses(['tahun_id' => $filters['tahun_id']]);
        if ($filters['prodi_id'] !== '') {
            $classes = array_values(array_filter(
                $classes,
                fn (array $r) => ($r['prodi_kode'] ?? '') === $filters['prodi_id'],
            ));
        }

        return $classes;
    }

    /**
     * @param  list<array<string, mixed>>  $classes
     * @return list<array<string, mixed>>
     */
    protected function filterClasses(array $classes, Request $request): array
    {
        if (! $request->boolean('only_selected')) {
            return $classes;
        }

        $keys = array_filter((array) $request->input('class_keys', []), 'is_string');
        if ($keys === []) {
            return [];
        }

        $allowed = array_flip($keys);

        return array_values(array_filter($classes, function (array $row) use ($allowed): bool {
            $key = ($row['mk_kode'] ?? '').'|'.($row['nama_kelas'] ?? '');

            return isset($allowed[$key]);
        }));
    }

    /**
     * @param  list<array<string, mixed>>  $participants
     * @return list<array<string, mixed>>
     */
    protected function filterParticipants(array $participants, Request $request): array
    {
        if (! $request->boolean('only_selected')) {
            return $participants;
        }

        $nims = array_filter((array) $request->input('nims', []), 'is_string');

        return array_values(array_filter(
            $participants,
            fn (array $r) => in_array((string) ($r['nim'] ?? ''), $nims, true),
        ));
    }

    protected function mergeResults(SyncBatchResult $a, SyncBatchResult $b): SyncBatchResult
    {
        return new SyncBatchResult(
            $a->successCount + $b->successCount,
            $a->failedCount + $b->failedCount,
            array_merge($a->successMessages, $b->successMessages),
            array_merge($a->errorCounts, $b->errorCounts),
            array_merge($a->failedItems, $b->failedItems),
        );
    }

    /**
     * @param  array<string, string>  $filters
     */
    protected function redirectSync(
        SyncBatchResult $result,
        string $label,
        array $filters,
        string $route = 'admin.kelas.index',
        array $extra = [],
    ): RedirectResponse {
        $redirect = redirect()->route($route, array_merge($filters, ['load' => 1], $extra));

        return SyncFlash::apply($redirect, $result, $label, 'kelas');
    }
}
