<?php

namespace App\Support\Feeder;

/**
 * SKS diakui untuk Insert/Update Riwayat Pendidikan (mahasiswa pindahan / RPL).
 * Sumber: TotalSKSPindah di tabel mhsw Siakad (via siakad-api).
 */
final class SksDiakuiResolver
{
    /**
     * @param  array<string, mixed>  $student
     */
    public static function resolve(array $student): ?int
    {
        foreach (['sks_diakui', 'total_sks_pindah', 'TotalSKSPindah'] as $key) {
            $parsed = self::parseInt($student[$key] ?? null);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        $totalSks = self::parseInt($student['total_sks'] ?? $student['TotalSKS'] ?? null);
        if ($totalSks !== null && $totalSks > 0) {
            return $totalSks;
        }

        return null;
    }

    public static function parseInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '' || ! is_numeric($value)) {
            return null;
        }

        return max(0, (int) $value);
    }
}
