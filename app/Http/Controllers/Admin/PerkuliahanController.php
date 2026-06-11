<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AcademicFilterResolver;
use App\Services\SiakadApiService;
use App\Services\Sync\PerkuliahanFeederService;
use App\Support\Sync\SyncFlash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class PerkuliahanController extends Controller
{
    public function __construct(
        protected AcademicFilterResolver $filterResolver,
    ) {}

    public function index(Request $request, SiakadApiService $siakad): View
    {
        $master = $this->fetchMaster($siakad);
        $scoped = $this->filterResolver->resolveScoped($request, $master);
        $master = $scoped['master'];
        $filters = $scoped['filters'];
        $rows = [];
        $error = $master['error'];
        $loaded = $request->boolean('load');

        if ($loaded && $error === null) {
            try {
                $rows = $siakad->fetchKhs(
                    array_filter([
                        'tahun_id' => $filters['tahun_id'],
                        'program_id' => $filters['program_id'],
                        'prodi_id' => $filters['prodi_id'],
                    ], fn (string $v) => $v !== ''),
                );
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        return view('admin.perkuliahan.index', [
            'title' => 'Aktivitas Kuliah Mahasiswa',
            'filters' => $filters,
            'master' => $master,
            'rows' => $rows,
            'error' => $error,
            'loaded' => $loaded,
        ]);
    }

    public function send(Request $request, SiakadApiService $siakad, PerkuliahanFeederService $sync): RedirectResponse
    {
        @set_time_limit(900);

        $filters = $this->validateSyncFilters($request);

        try {
            $rows = $siakad->fetchKhs(
                array_filter($filters, fn (string $v) => $v !== ''),
            );
            $rows = $this->filterSelectedRows($rows, $request);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('admin.perkuliahan.index', array_merge($filters, ['load' => 1]))
                ->with('error', $e->getMessage());
        }

        if ($rows === []) {
            return redirect()
                ->route('admin.perkuliahan.index', array_merge($filters, ['load' => 1]))
                ->with('error', 'Tidak ada data KHS untuk dikirim.');
        }

        try {
            $result = $sync->insertPerkuliahanMahasiswa($rows);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('admin.perkuliahan.index', array_merge($filters, ['load' => 1]))
                ->with('error', $e->getMessage());
        }

        $redirect = redirect()->route('admin.perkuliahan.index', array_merge($filters, ['load' => 1]));

        return SyncFlash::apply($redirect, $result, 'Kirim aktivitas kuliah', 'perkuliahan');
    }

    /**
     * @return array{
     *   programs: list<array<string, mixed>>,
     *   prodi: list<array<string, mixed>>,
     *   tahun: list<array<string, mixed>>,
     *   status_awal: list<array<string, mixed>>,
     *   error: string|null
     * }
     */
    protected function fetchMaster(SiakadApiService $siakad): array
    {
        $master = [
            'programs' => [],
            'prodi' => [],
            'tahun' => [],
            'status_awal' => [],
            'error' => null,
        ];

        try {
            $master['programs'] = $siakad->fetchPrograms();
            $master['prodi'] = $siakad->fetchStudyPrograms();
            $master['tahun'] = $siakad->fetchAcademicYears();
        } catch (RuntimeException $e) {
            $master['error'] = $e->getMessage();
        }

        return $master;
    }

    /**
     * @return array{program_id: string, prodi_id: string, tahun_id: string}
     */
    protected function validateSyncFilters(Request $request): array
    {
        $validated = $request->validate([
            'program_id' => ['required', 'string', 'max:50'],
            'prodi_id' => ['required', 'string', 'max:120'],
            'tahun_id' => ['required', 'string', 'max:20'],
            'only_selected' => ['nullable', 'boolean'],
            'nims' => ['nullable', 'array'],
            'nims.*' => ['string', 'max:50'],
        ]);

        return app(\App\Services\ProdiAccessService::class)->enforceFilters([
            'program_id' => (string) $validated['program_id'],
            'prodi_id' => (string) $validated['prodi_id'],
            'tahun_id' => (string) $validated['tahun_id'],
        ]);
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
