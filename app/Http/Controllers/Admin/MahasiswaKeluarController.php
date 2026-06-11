<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AcademicFilterResolver;
use App\Services\SiakadApiService;
use App\Services\Sync\MahasiswaKeluarFeederService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class MahasiswaKeluarController extends Controller
{
    public function __construct(
        protected AcademicFilterResolver $filterResolver,
    ) {}

    public function index(Request $request, SiakadApiService $siakad): View
    {
        $master = $this->fetchMaster($siakad);
        $scoped = $this->filterResolver->resolveScoped($request, $master);
        $master = $scoped['master'];
        $filters = $this->extendFilters($request, $master, $scoped['filters']);
        $rows = [];
        $error = $master['error'];
        $loaded = $request->boolean('load');

        if ($loaded && $error === null) {
            try {
                $rows = $siakad->fetchStudentExit($this->apiQuery($filters));
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        return view('admin.mahasiswa-keluar.index', [
            'title' => 'Mahasiswa Lulus / DO',
            'filters' => $filters,
            'master' => $master,
            'rows' => $rows,
            'error' => $error,
            'loaded' => $loaded,
        ]);
    }

    public function send(Request $request, SiakadApiService $siakad, MahasiswaKeluarFeederService $sync): RedirectResponse
    {
        @set_time_limit(900);

        $validated = $request->validate([
            'program_id' => ['required', 'string', 'max:50'],
            'prodi_id' => ['required', 'string', 'max:120'],
            'angkatan' => ['required', 'string', 'max:20'],
            'status_lulus_id' => ['required', 'string', 'max:10'],
            'only_selected' => ['nullable', 'boolean'],
            'nims' => ['nullable', 'array'],
            'nims.*' => ['string', 'max:50'],
        ]);

        $filters = app(\App\Services\ProdiAccessService::class)->enforceFilters([
            'program_id' => (string) $validated['program_id'],
            'prodi_id' => (string) $validated['prodi_id'],
            'angkatan' => (string) $validated['angkatan'],
            'status_lulus_id' => (string) $validated['status_lulus_id'],
        ]);

        try {
            $rows = $siakad->fetchStudentExit($this->apiQuery($filters));
            $rows = $this->filterSelectedRows($rows, $request);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('admin.mahasiswa-keluar.index', array_merge($filters, ['load' => 1]))
                ->with('error', $e->getMessage());
        }

        if ($rows === []) {
            return redirect()
                ->route('admin.mahasiswa-keluar.index', array_merge($filters, ['load' => 1]))
                ->with('error', 'Tidak ada mahasiswa untuk dikirim.');
        }

        try {
            $result = $sync->insertMahasiswaLulusDo($rows);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('admin.mahasiswa-keluar.index', array_merge($filters, ['load' => 1]))
                ->with('error', $e->getMessage());
        }

        $redirect = redirect()->route('admin.mahasiswa-keluar.index', array_merge($filters, ['load' => 1]));

        if ($success = $result->flashSuccess()) {
            $redirect = $redirect->with('success', "Kirim lulus/DO: {$success}");
        }
        if ($failed = $result->flashError()) {
            $redirect = $redirect->with('error', "Kirim lulus/DO: {$failed}");
        }

        return $redirect;
    }

    /**
     * @return array{
     *   programs: list<array<string, mixed>>,
     *   prodi: list<array<string, mixed>>,
     *   cohorts: list<array<string, mixed>>,
     *   status_lulus: list<array<string, mixed>>,
     *   error: string|null
     * }
     */
    protected function fetchMaster(SiakadApiService $siakad): array
    {
        $master = [
            'programs' => [],
            'prodi' => [],
            'cohorts' => [],
            'status_lulus' => [],
            'error' => null,
        ];

        try {
            $master['programs'] = $siakad->fetchPrograms();
            $master['prodi'] = $siakad->fetchStudyPrograms();
            $master['cohorts'] = $siakad->fetchCohorts();
            $master['status_lulus'] = $siakad->fetchGraduationStatus();
        } catch (RuntimeException $e) {
            $master['error'] = $e->getMessage();
        }

        return $master;
    }

    /**
     * @param  array{programs: list<array<string, mixed>>, prodi: list<array<string, mixed>>, cohorts: list<array<string, mixed>>, status_lulus: list<array<string, mixed>>}  $master
     * @return array{program_id: string, prodi_id: string, angkatan: string, status_lulus_id: string}
     */
    /**
     * @param  array<string, mixed>  $master
     * @param  array<string, mixed>  $base
     */
    protected function extendFilters(Request $request, array $master, array $base): array
    {
        $angkatan = $request->string('angkatan')->toString();

        if ($angkatan === '') {
            foreach ($master['cohorts'] as $row) {
                $id = $row['id'] ?? '';
                if (is_string($id) && trim($id) !== '') {
                    $angkatan = trim($id);
                    break;
                }
            }
        }

        $statusLulusId = $request->string('status_lulus_id')->toString();
        if ($statusLulusId === '') {
            foreach ($master['status_lulus'] as $row) {
                $id = $row['id'] ?? '';
                if (is_string($id) && trim($id) !== '') {
                    $statusLulusId = trim($id);
                    break;
                }
            }
        }

        return [
            'program_id' => $base['program_id'],
            'prodi_id' => $base['prodi_id'],
            'angkatan' => $angkatan,
            'status_lulus_id' => $statusLulusId,
        ];
    }

    /**
     * @param  array{program_id: string, prodi_id: string, angkatan: string, status_lulus_id: string}  $filters
     * @return array<string, string>
     */
    protected function apiQuery(array $filters): array
    {
        return array_filter([
            'program_id' => $filters['program_id'],
            'prodi_id' => $filters['prodi_id'],
            'angkatan' => $filters['angkatan'],
            'status_lulus_id' => $filters['status_lulus_id'],
        ], fn (string $v) => $v !== '');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    protected function filterSelectedRows(array $rows, Request $request): array
    {
        if (! $request->boolean('only_selected')) {
            return $rows;
        }

        $nims = array_filter((array) $request->input('nims', []), fn ($v) => is_string($v) && $v !== '');
        if ($nims === []) {
            return [];
        }

        $allowed = array_flip($nims);

        return array_values(array_filter($rows, function (array $row) use ($allowed): bool {
            $nim = (string) ($row['nim'] ?? $row['mhsw_id'] ?? '');

            return isset($allowed[$nim]);
        }));
    }
}
