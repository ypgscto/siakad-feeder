<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeederCodeMap extends Model
{
    public const CATEGORY_AGAMA = 'agama';

    public const CATEGORY_JENIS_DAFTAR = 'jenis_daftar';

    public const CATEGORY_JENIS_KELUAR = 'jenis_keluar';

    public const CATEGORY_KELAMIN = 'kelamin';

    public const CATEGORY_STATUS_MAHASISWA = 'status_mahasiswa';

    /**
     * @return list<string>
     */
    public static function categories(): array
    {
        return [
            self::CATEGORY_AGAMA,
            self::CATEGORY_JENIS_DAFTAR,
            self::CATEGORY_JENIS_KELUAR,
            self::CATEGORY_STATUS_MAHASISWA,
            self::CATEGORY_KELAMIN,
        ];
    }

    protected $fillable = [
        'category',
        'siakad_key',
        'siakad_label',
        'feeder_value',
        'feeder_label',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
