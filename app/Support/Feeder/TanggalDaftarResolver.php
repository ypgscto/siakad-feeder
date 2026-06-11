<?php

namespace App\Support\Feeder;

/**
 * Menentukan tanggal_daftar untuk InsertRiwayatPendidikanMahasiswa.
 *
 * Feeder menolak jika tanggal masuk < tanggal mulai semester (id_periode_masuk).
 * Sumber terbaik: TglKuliahMulai dari tabel tahun Siakad (via siakad-api).
 */
final class TanggalDaftarResolver
{
    /**
     * @param  array<string, mixed>  $student
     */
    public static function resolve(array $student): string
    {
        $fromSiakad = self::normalizeDate((string) ($student['tgl_kuliah_mulai'] ?? ''));
        if ($fromSiakad !== null) {
            return $fromSiakad;
        }

        return self::fromTahunId((string) ($student['tahun_id'] ?? ''));
    }

    public static function fromTahunId(string $tahunId): string
    {
        $tahunId = trim($tahunId);
        if ($tahunId === '') {
            return date('Y-m-d');
        }

        if (strlen($tahunId) >= 5 && ctype_digit($tahunId)) {
            $year = (int) substr($tahunId, 0, 4);
            $semesterDigit = substr($tahunId, -1);

            if (in_array($semesterDigit, ['1', '3'], true)) {
                return sprintf('%04d-%s', $year, config('feeder_maps.tanggal_daftar.ganjil', '09-01'));
            }

            if (in_array($semesterDigit, ['2', '4'], true)) {
                $offset = (int) config('feeder_maps.tanggal_daftar.genap_year_offset', 1);

                return sprintf(
                    '%04d-%s',
                    $year + $offset,
                    config('feeder_maps.tanggal_daftar.genap', '02-01'),
                );
            }
        }

        $year = substr($tahunId, 0, 4);
        if (preg_match('/^\d{4}$/', $year) === 1) {
            return $year.'-'.config('feeder_maps.tanggal_daftar.ganjil', '09-01');
        }

        return date('Y-m-d');
    }

    public static function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $value, $m) === 1) {
            return $m[1].'-'.$m[2].'-'.$m[3];
        }

        if (preg_match('/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/', $value, $m) === 1) {
            return $m[3].'-'.$m[2].'-'.$m[1];
        }

        $timestamp = strtotime($value);

        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }
}
