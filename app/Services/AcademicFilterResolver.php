<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Default filter UI dari master Siakad-API (bukan nilai hardcoded kampus).
 */
class AcademicFilterResolver
{
    /**
     * @param  array{
     *   programs?: list<array<string, mixed>>,
     *   prodi?: list<array<string, mixed>>,
     *   tahun?: list<array<string, mixed>>,
     *   status_awal?: list<array<string, mixed>>
     * }  $master
     * @return array{program_id: string, prodi_id: string, tahun_id: string, status_awal_id: string}
     */
    public function resolve(Request $request, array $master): array
    {
        $filters = [
            'program_id' => $this->pick($request, 'program_id', $master['programs'] ?? []),
            'prodi_id' => $this->pick($request, 'prodi_id', $master['prodi'] ?? []),
            'tahun_id' => $this->pick($request, 'tahun_id', $master['tahun'] ?? [], ['id', 'tahun_id', 'TahunID']),
            'status_awal_id' => $this->pick($request, 'status_awal_id', $master['status_awal'] ?? []),
        ];

        return app(ProdiAccessService::class)->enforceFilters($filters, Auth::user());
    }

    /**
     * @param  array<string, mixed>  $master
     * @return array{master: array<string, mixed>, filters: array<string, mixed>}
     */
    public function resolveScoped(Request $request, array $master): array
    {
        $filters = [
            'program_id' => $this->pick($request, 'program_id', $master['programs'] ?? []),
            'prodi_id' => $this->pick($request, 'prodi_id', $master['prodi'] ?? []),
            'tahun_id' => $this->pick($request, 'tahun_id', $master['tahun'] ?? [], ['id', 'tahun_id', 'TahunID']),
            'status_awal_id' => $this->pick($request, 'status_awal_id', $master['status_awal'] ?? []),
        ];

        [$master, $filters] = app(ProdiAccessService::class)->scope($master, $filters, Auth::user());

        return [
            'master' => $master,
            'filters' => $filters,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $idKeys
     */
    protected function pick(Request $request, string $field, array $rows, array $idKeys = ['id', 'ProgramID', 'ProdiID', 'StatusAwalID']): string
    {
        $fromRequest = $request->string($field)->toString();
        if ($fromRequest !== '') {
            return $fromRequest;
        }

        foreach ($rows as $row) {
            foreach ($idKeys as $key) {
                $value = $row[$key] ?? null;
                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }
            }
        }

        return '';
    }
}
