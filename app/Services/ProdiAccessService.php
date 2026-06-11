<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ProdiAccessService
{
    public function isScopedUser(?User $user = null): bool
    {
        $user ??= Auth::user();

        return $user?->isProdi() ?? false;
    }

    public function assignedProdiId(?User $user = null): ?string
    {
        $user ??= Auth::user();

        if ($user === null || ! $user->isProdi()) {
            return null;
        }

        $prodiId = trim((string) $user->prodi_id);

        return $prodiId !== '' ? $prodiId : null;
    }

    /**
     * @param  array<string, mixed>  $master
     * @param  array<string, mixed>  $filters
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    public function scope(array $master, array $filters, ?User $user = null): array
    {
        $user ??= Auth::user();
        $assigned = $this->assignedProdiId($user);

        if ($assigned === null) {
            return [$master, $filters];
        }

        if (isset($master['prodi']) && is_array($master['prodi'])) {
            $master['prodi'] = $this->filterProdiList($master['prodi'], $assigned);
        }

        if (array_key_exists('prodi_id', $filters)) {
            $filters['prodi_id'] = $this->enforceProdiId($assigned, (string) $filters['prodi_id']);
        }

        return [$master, $filters];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function enforceFilters(array $filters, ?User $user = null): array
    {
        $user ??= Auth::user();
        $assigned = $this->assignedProdiId($user);

        if ($assigned === null) {
            return $filters;
        }

        if (array_key_exists('prodi_id', $filters)) {
            $filters['prodi_id'] = $this->enforceProdiId($assigned, (string) $filters['prodi_id']);
        }

        return $filters;
    }

    public function enforceProdiId(string $assigned, string $requested): string
    {
        if ($requested !== '' && $requested !== $assigned) {
            abort(403, 'Anda hanya dapat mengakses program studi yang ditugaskan.');
        }

        return $assigned;
    }

    /**
     * @param  list<array<string, mixed>>  $prodiRows
     * @return list<array<string, mixed>>
     */
    public function filterProdiList(array $prodiRows, string $assignedProdiId): array
    {
        return array_values(array_filter(
            $prodiRows,
            fn (array $row): bool => $this->extractProdiIdFromRow($row) === $assignedProdiId,
        ));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function extractProdiIdFromRow(array $row): string
    {
        foreach (['id', 'ProdiID', 'prodi_id', 'kode', 'kode_prodi'] as $key) {
            $value = trim((string) ($row[$key] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @param  list<array<string, mixed>>  $prodiRows
     */
    public function prodiLabel(array $prodiRows, string $prodiId): string
    {
        foreach ($prodiRows as $row) {
            if ($this->extractProdiIdFromRow($row) === $prodiId) {
                return (string) ($row['nama'] ?? $row['Nama'] ?? $prodiId);
            }
        }

        return $prodiId;
    }
}
