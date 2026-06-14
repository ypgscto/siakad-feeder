<?php

namespace App\Support\Sync;

final class KelasSelectionKey
{
    /**
     * Kunci unik baris jadwal kelas (bukan mk_kode + nama_kelas).
     *
     * @param  array<string, mixed>  $row
     */
    public static function fromRow(array $row): string
    {
        return (string) ($row['id'] ?? $row['siakad_id'] ?? '');
    }
}
