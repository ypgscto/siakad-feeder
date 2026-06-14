<?php

namespace App\Support\Feeder;

/**
 * Menentukan nama kelas untuk Feeder (field nama_kelas_kuliah).
 *
 * Siakad menyimpan ID internal di jadwal.NamaKelas (nama_kelas, mis. "382")
 * dan nama tampilan di kelas.Nama (kelas_nama, mis. "3A").
 */
final class KelasNamaResolver
{
    /**
     * @param  array<string, mixed>  $row
     */
    public static function forFeeder(array $row): string
    {
        $display = trim((string) ($row['kelas_nama'] ?? ''));
        if ($display !== '') {
            return $display;
        }

        return trim((string) ($row['nama_kelas'] ?? $row['kode_kelas'] ?? ''));
    }

    public static function fromRequest(string $kelasNama, string $namaKelas): string
    {
        $display = trim($kelasNama);
        if ($display !== '') {
            return $display;
        }

        return trim($namaKelas);
    }
}
