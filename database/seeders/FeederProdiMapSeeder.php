<?php

namespace Database\Seeders;

use App\Models\FeederProdiMap;
use Illuminate\Database\Seeder;

class FeederProdiMapSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('feeder_maps.prodi', []) as $siakadProdiId => $map) {
            if (! is_array($map) || empty($map['id_prodi'])) {
                continue;
            }

            FeederProdiMap::query()->updateOrCreate(
                ['siakad_prodi_id' => (string) $siakadProdiId],
                [
                    'feeder_id_prodi' => (string) $map['id_prodi'],
                    'feeder_id_prodi_asal' => $map['prodi_asal'] ?? null,
                    'feeder_id_prodi_rpl' => $map['prodi_rpl'] ?? null,
                    'is_active' => true,
                ],
            );
        }
    }
}
