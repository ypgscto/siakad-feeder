<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pemetaan pendukung: Siakad ProdiID → UUID prodi di Neo Feeder.
 * Bukan master program studi (master ada di Siakad / siakad-api).
 */
class FeederProdiMap extends Model
{
    protected $fillable = [
        'siakad_prodi_id',
        'feeder_id_prodi',
        'feeder_id_prodi_asal',
        'feeder_id_prodi_rpl',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
