<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AcademicFilterResolver;
use App\Services\Feeder\FeederClient;
use App\Services\SiakadApiService;
use App\Services\Sync\MahasiswaFeederService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class MahasiswaController extends Controller
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

        $students = [];
        $error = $master['error'];
        $loaded = $request->boolean('load');

        if ($loaded && $error === null) {
            try {
                $students = $siakad->fetchMahasiswaSync(
                    array_filter($filters, fn (string $v) => $v !== ''),
                );
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        return view('admin.mahasiswa.index', [
            'title' => 'Data Mahasiswa',
            'filters' => $filters,
            'master' => $master,
            'students' => $students,
            'error' => $error,
            'loaded' => $loaded,
        ]);
    }

    public function sendFull(Request $request, SiakadApiService $siakad, MahasiswaFeederService $sync): RedirectResponse
    {
        return $this->runSync($request, $siakad, $sync, 'sendBiodataAndRiwayat', 'Kirim biodata + riwayat');
    }

    public function sendRiwayat(Request $request, SiakadApiService $siakad, MahasiswaFeederService $sync): RedirectResponse
    {
        return $this->runSync($request, $siakad, $sync, 'sendRiwayatOnly', 'Kirim riwayat pendidikan');
    }

    public function updateRiwayat(Request $request, SiakadApiService $siakad, MahasiswaFeederService $sync): RedirectResponse
    {
        return $this->runSync($request, $siakad, $sync, 'updateRiwayat', 'Update riwayat pendidikan');
    }

    protected function runSync(
        Request $request,
        SiakadApiService $siakad,
        MahasiswaFeederService $sync,
        string $method,
        string $label,
    ): RedirectResponse {
        @set_time_limit(900);

        $filters = $this->validateSyncFilters($request);

        try {
            app(FeederClient::class)->ping();
        } catch (RuntimeException $e) {
            return redirect()
                ->route('admin.mahasiswa.index', array_merge($filters, ['load' => 1]))
                ->with('error', 'Neo Feeder tidak siap: '.$e->getMessage());
        }

        try {
            $query = array_filter($filters, fn (string $v) => $v !== '');
            $nims = $this->selectedNims($request);
            if ($request->boolean('only_selected') && $nims !== []) {
                $query['nims'] = implode(',', $nims);
            }

            $students = $siakad->fetchMahasiswaSync($query);
            $students = $this->filterSelectedStudents($students, $request);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('admin.mahasiswa.index', array_merge($filters, ['load' => 1]))
                ->with('error', $e->getMessage());
        }

        if ($students === []) {
            return redirect()
                ->route('admin.mahasiswa.index', array_merge($filters, ['load' => 1]))
                ->with('error', 'Tidak ada mahasiswa untuk dikirim. Centang minimal satu baris mahasiswa.');
        }

        try {
            app(FeederClient::class)->ensureReadyForWrite();
        } catch (RuntimeException $e) {
            return redirect()
                ->route('admin.mahasiswa.index', array_merge($filters, ['load' => 1]))
                ->with('error', 'Neo Feeder tidak siap untuk kirim data: '.$e->getMessage());
        }

        try {
            $result = $sync->{$method}($students, $filters['status_awal_id']);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('admin.mahasiswa.index', array_merge($filters, ['load' => 1]))
                ->with('error', $e->getMessage());
        }

        $redirect = redirect()->route('admin.mahasiswa.index', array_merge($filters, ['load' => 1]));

        if ($success = $result->flashSuccess()) {
            $redirect = $redirect->with('success', "{$label}: {$success}");
        }

        if ($failed = $result->flashError()) {
            $redirect = $redirect->with('error', "{$label}: {$failed}");
        }

        return $redirect;
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
            $master['status_awal'] = $siakad->fetchStatusAwal();
        } catch (RuntimeException $e) {
            $master['error'] = $e->getMessage();
        }

        return $master;
    }

    /**
     * @return array{program_id: string, prodi_id: string, tahun_id: string, status_awal_id: string}
     */
    protected function validateSyncFilters(Request $request): array
    {
        $validated = $request->validate([
            'program_id' => ['required', 'string', 'max:50'],
            'prodi_id' => ['required', 'string', 'max:120'],
            'tahun_id' => ['required', 'string', 'max:20'],
            'status_awal_id' => ['required', 'string', 'max:10'],
            'only_selected' => ['nullable', 'boolean'],
            'nims' => ['nullable', 'array'],
            'nims.*' => ['string', 'max:50'],
        ]);

        return app(\App\Services\ProdiAccessService::class)->enforceFilters([
            'program_id' => (string) $validated['program_id'],
            'prodi_id' => (string) $validated['prodi_id'],
            'tahun_id' => (string) $validated['tahun_id'],
            'status_awal_id' => (string) $validated['status_awal_id'],
        ]);
    }

    /**
     * @return list<string>
     */
    protected function selectedNims(Request $request): array
    {
        return array_values(array_filter(
            (array) $request->input('nims', []),
            fn ($v) => is_string($v) && $v !== '',
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $students
     * @return list<array<string, mixed>>
     */
    protected function filterSelectedStudents(array $students, Request $request): array
    {
        if (! $request->boolean('only_selected')) {
            return $students;
        }

        $nims = $this->selectedNims($request);
        if ($nims === []) {
            return [];
        }

        $allowed = array_flip($nims);

        return array_values(array_filter($students, function (array $row) use ($allowed): bool {
            $nim = (string) ($row['nim'] ?? $row['mhsw_id'] ?? '');

            return isset($allowed[$nim]);
        }));
    }
}
