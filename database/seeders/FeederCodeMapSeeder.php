<?php

namespace Database\Seeders;

use App\Models\FeederCodeMap;
use Illuminate\Database\Seeder;

class FeederCodeMapSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategory(FeederCodeMap::CATEGORY_AGAMA, config('feeder_maps.agama', []));
        $this->seedCategory(
            FeederCodeMap::CATEGORY_JENIS_DAFTAR,
            config('feeder_maps.jenis_daftar', []),
            config('siakad_reference.statusawal', []),
        );
        $this->seedCategory(
            FeederCodeMap::CATEGORY_JENIS_KELUAR,
            config('feeder_maps.jenis_keluar', []),
            config('siakad_reference.statusmhsw', []),
        );
        $this->seedCategory(
            FeederCodeMap::CATEGORY_STATUS_MAHASISWA,
            config('feeder_maps.status_mahasiswa', []),
            config('siakad_reference.statusmhsw', []),
        );
        $this->seedCategory(FeederCodeMap::CATEGORY_KELAMIN, config('feeder_maps.kelamin', []));
    }

    /**
     * @param  array<string, string>  $maps
     * @param  array<string, string>  $siakadLabels
     */
    protected function seedCategory(string $category, array $maps, array $siakadLabels = []): void
    {
        $service = app(\App\Services\Feeder\FeederCodeMapService::class);
        $refCategory = $category === FeederCodeMap::CATEGORY_STATUS_MAHASISWA
            ? 'status_mahasiswa'
            : $category;

        foreach ($maps as $key => $value) {
            if ($key === 'default' || ! is_string($value) || $value === '') {
                continue;
            }

            FeederCodeMap::query()->updateOrCreate(
                ['category' => $category, 'siakad_key' => (string) $key],
                [
                    'siakad_label' => $siakadLabels[$key] ?? (string) $key,
                    'feeder_value' => $value,
                    'feeder_label' => $service->feederLabel($refCategory, $value)
                        ?? config("feeder_reference.id_status_mahasiswa.{$value}"),
                    'is_active' => true,
                ],
            );
        }
    }
}
