<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Services\AcademicFilterResolver;
use App\Services\ProdiAccessService;
use App\Services\SiakadApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

trait LoadsAcademicMaster
{
    /**
     * @return array{
     *   prodi: list<array<string, mixed>>,
     *   tahun: list<array<string, mixed>>,
     *   error: string|null
     * }
     */
    protected function fetchProdiTahunMaster(SiakadApiService $siakad): array
    {
        $master = [
            'prodi' => [],
            'tahun' => [],
            'error' => null,
        ];

        try {
            $master['prodi'] = $siakad->fetchStudyPrograms();
            $master['tahun'] = $siakad->fetchAcademicYears();
        } catch (RuntimeException $e) {
            $master['error'] = $e->getMessage();
        }

        return $this->scopeMaster($master);
    }

    /**
     * @param  array<string, mixed>  $master
     * @return array<string, mixed>
     */
    protected function scopeMaster(array $master): array
    {
        [$master] = app(ProdiAccessService::class)->scope($master, [], Auth::user());

        return $master;
    }

    /**
     * @param  array{prodi: list<array<string, mixed>>, tahun: list<array<string, mixed>>}  $master
     * @return array{prodi_id: string, tahun_id: string}
     */
    protected function resolveProdiTahunFilters(Request $request, array $master): array
    {
        /** @var AcademicFilterResolver $resolver */
        $resolver = app(AcademicFilterResolver::class);

        $scoped = $resolver->resolveScoped($request, array_merge($master, [
            'programs' => [],
            'status_awal' => [],
        ]));

        return [
            'prodi_id' => $scoped['filters']['prodi_id'],
            'tahun_id' => $scoped['filters']['tahun_id'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function enforceProdiOnFilters(array $filters): array
    {
        return app(ProdiAccessService::class)->enforceFilters($filters, Auth::user());
    }
}
