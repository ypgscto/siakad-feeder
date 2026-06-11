<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AcademicFilterResolver;
use App\Services\SiakadApiService;
use App\Services\Sync\KonversiNilaiFeederService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class KonversiNilaiController extends Controller
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
        $students = [];
        $error = $master['error'];
        $loaded = $request->boolean('load');

        if ($loaded && $error === null) {
            try {
                $students = $this->uniqueStudents(
                    $siakad->fetchConversionGrades($this->apiQuery($filters)),
                );
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        return view('admin.konversi-nilai.index', [
            'title' => 'Konversi Nilai Pindahan',
            'filters' => $filters,
            'master' => $master,
            'students' => $students,
            'error' => $error,
            'loaded' => $loaded,
        ]);
    }

    public function matakuliah(Request $request, SiakadApiService $siakad): View
    {
        $master = $this->fetchMaster($siakad);
        $scoped = $this->filterResolver->resolveScoped($request, $master);
        $master = $scoped['master'];
        $filters = $this->extendFilters($request, $master, $scoped['filters']);
        $mhswId = $request->string('mhsw_id')->toString();
        $nim = $request->string('nim')->toString();
        $nama = $request->string('nama')->toString();

        $rows = [];
        $error = $master['error'];

        if (($mhswId !== '' || $nim !== '') && $error === null) {
            try {
                $query = $this->apiQuery($filters);
                if ($mhswId !== '') {
                    $query['mhsw_id'] = $mhswId;
                }
                if ($nim !== '') {
                    $query['nim'] = $nim;
                }
                $rows = $siakad->fetchConversionGrades($query);
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        return view('admin.konversi-nilai.matakuliah', [
            'title' => 'Nilai Konversi Mahasiswa',
            'filters' => $filters,
            'master' => $master,
            'mhswId' => $mhswId,
            'nim' => $nim,
            'nama' => $nama,
            'rows' => $rows,
            'error' => $error,
        ]);
    }

    public function send(Request $request, SiakadApiService $siakad, KonversiNilaiFeederService $sync): RedirectResponse
    {
        @set_time_limit(900);

        $validated = $request->validate([
            'program_id' => ['required', 'string', 'max:50'],
            'prodi_id' => ['required', 'string', 'max:120'],
            'angkatan' => ['required', 'string', 'max:20'],
            'status_awal_id' => ['nullable', 'string', 'max:10'],
            'mhsw_id' => ['required', 'string', 'max:50'],
            'nim' => ['required', 'string', 'max:50'],
            'nama' => ['nullable', 'string', 'max:200'],
            'only_selected' => ['nullable', 'boolean'],
            'mk_kodes' => ['nullable', 'array'],
            'mk_kodes.*' => ['string', 'max:50'],
        ]);

        $filters = app(\App\Services\ProdiAccessService::class)->enforceFilters([
            'program_id' => (string) $validated['program_id'],
            'prodi_id' => (string) $validated['prodi_id'],
            'angkatan' => (string) $validated['angkatan'],
            'status_awal_id' => (string) ($validated['status_awal_id'] ?? ''),
        ]);

        try {
            $query = array_merge($this->apiQuery($filters), [
                'mhsw_id' => (string) $validated['mhsw_id'],
                'nim' => (string) $validated['nim'],
            ]);
            $rows = $siakad->fetchConversionGrades($query);
            $rows = $this->filterSelectedRows($rows, $request);
        } catch (RuntimeException $e) {
            return $this->redirectMatakuliah($validated, $filters, (string) ($validated['nama'] ?? ''))
                ->with('error', $e->getMessage());
        }

        if ($rows === []) {
            return $this->redirectMatakuliah($validated, $filters, (string) ($validated['nama'] ?? ''))
                ->with('error', 'Tidak ada mata kuliah untuk dikirim.');
        }

        try {
            $result = $sync->insertNilaiTransfer($rows);
        } catch (RuntimeException $e) {
            return $this->redirectMatakuliah($validated, $filters, (string) ($validated['nama'] ?? ''))
                ->with('error', $e->getMessage());
        }

        $redirect = $this->redirectMatakuliah($validated, $filters, (string) ($validated['nama'] ?? ''));

        if ($success = $result->flashSuccess()) {
            $redirect = $redirect->with('success', "Kirim konversi nilai: {$success}");
        }
        if ($failed = $result->flashError()) {
            $redirect = $redirect->with('error', "Kirim konversi nilai: {$failed}");
        }

        return $redirect;
    }

    /**
     * @return array{
     *   programs: list<array<string, mixed>>,
     *   prodi: list<array<string, mixed>>,
     *   cohorts: list<array<string, mixed>>,
     *   status_awal: list<array<string, mixed>>,
     *   error: string|null
     * }
     */
    protected function fetchMaster(SiakadApiService $siakad): array
    {
        $master = [
            'programs' => [],
            'prodi' => [],
            'cohorts' => [],
            'status_awal' => [],
            'error' => null,
        ];

        try {
            $master['programs'] = $siakad->fetchPrograms();
            $master['prodi'] = $siakad->fetchStudyPrograms();
            $master['cohorts'] = $siakad->fetchCohorts();
            $master['status_awal'] = $siakad->fetchStatusAwal();
        } catch (RuntimeException $e) {
            $master['error'] = $e->getMessage();
        }

        return $master;
    }

    /**
     * @param  array{programs: list<array<string, mixed>>, prodi: list<array<string, mixed>>, cohorts: list<array<string, mixed>>, status_awal: list<array<string, mixed>>}  $master
     * @return array{program_id: string, prodi_id: string, angkatan: string, status_awal_id: string}
     */
    /**
     * @param  array<string, mixed>  $master
     * @param  array<string, mixed>  $base
     * @return array{program_id: string, prodi_id: string, angkatan: string, status_awal_id: string}
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

        return [
            'program_id' => $base['program_id'],
            'prodi_id' => $base['prodi_id'],
            'angkatan' => $angkatan,
            'status_awal_id' => $request->string('status_awal_id')->toString(),
        ];
    }

    /**
     * @param  array{program_id: string, prodi_id: string, angkatan: string, status_awal_id: string}  $filters
     * @return array<string, string>
     */
    protected function apiQuery(array $filters): array
    {
        $query = array_filter([
            'program_id' => $filters['program_id'],
            'prodi_id' => $filters['prodi_id'],
            'angkatan' => $filters['angkatan'],
        ], fn (string $v) => $v !== '');

        if ($filters['status_awal_id'] !== '') {
            $query['status_awal_id'] = $filters['status_awal_id'];
        }

        return $query;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    protected function uniqueStudents(array $rows): array
    {
        $seen = [];
        $students = [];

        foreach ($rows as $row) {
            $key = (string) ($row['mhsw_id'] ?? $row['nim'] ?? '');
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $students[] = [
                'mhsw_id' => (string) ($row['mhsw_id'] ?? ''),
                'nim' => (string) ($row['nim'] ?? ''),
                'nama' => (string) ($row['nama_mahasiswa'] ?? ''),
                'status_awal_id' => (string) ($row['status_awal_id'] ?? ''),
                'status_awal_nama' => (string) ($row['status_awal_nama'] ?? ''),
                'prodi_id' => (string) ($row['prodi_id'] ?? ''),
            ];
        }

        return $students;
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

        $codes = array_filter((array) $request->input('mk_kodes', []), fn ($v) => is_string($v) && $v !== '');
        if ($codes === []) {
            return [];
        }

        $allowed = array_flip($codes);

        return array_values(array_filter(
            $rows,
            fn (array $r) => isset($allowed[trim((string) ($r['mk_kode'] ?? ''))]),
        ));
    }

    /**
     * @param  array<string, string>  $validated
     * @param  array{program_id: string, prodi_id: string, angkatan: string, status_awal_id: string}  $filters
     */
    protected function redirectMatakuliah(array $validated, array $filters, string $nama = ''): RedirectResponse
    {
        return redirect()->route('admin.konversi-nilai.matakuliah', array_merge($filters, [
            'mhsw_id' => $validated['mhsw_id'],
            'nim' => $validated['nim'],
            'nama' => $nama !== '' ? $nama : ($validated['nama'] ?? ''),
        ]));
    }
}
