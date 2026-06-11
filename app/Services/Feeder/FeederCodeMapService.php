<?php

namespace App\Services\Feeder;

use App\Models\FeederCodeMap;
use RuntimeException;

class FeederCodeMapService
{
    /**
     * @return array<string, string>
     */
    protected function configPathByCategory(): array
    {
        return [
            FeederCodeMap::CATEGORY_AGAMA => 'feeder_maps.agama',
            FeederCodeMap::CATEGORY_JENIS_DAFTAR => 'feeder_maps.jenis_daftar',
            FeederCodeMap::CATEGORY_JENIS_KELUAR => 'feeder_maps.jenis_keluar',
            FeederCodeMap::CATEGORY_KELAMIN => 'feeder_maps.kelamin',
            FeederCodeMap::CATEGORY_STATUS_MAHASISWA => 'feeder_maps.status_mahasiswa',
        ];
    }

    public function resolve(string $category, string $siakadKey, ?string $fallback = null): ?string
    {
        $siakadKey = trim($siakadKey);
        if ($siakadKey === '') {
            return $fallback;
        }

        $row = FeederCodeMap::query()
            ->where('category', $category)
            ->where('siakad_key', $siakadKey)
            ->where('is_active', true)
            ->first();

        if ($row) {
            return (string) $row->feeder_value;
        }

        $configPath = $this->configPathByCategory()[$category] ?? null;
        if ($configPath !== null) {
            $fromConfig = config("{$configPath}.{$siakadKey}");
            if (is_string($fromConfig) && $fromConfig !== '') {
                return $fromConfig;
            }
        }

        if (in_array($category, [FeederCodeMap::CATEGORY_JENIS_KELUAR, FeederCodeMap::CATEGORY_STATUS_MAHASISWA], true)) {
            $configKey = $category === FeederCodeMap::CATEGORY_JENIS_KELUAR ? 'jenis_keluar' : 'status_mahasiswa';
            $default = config("feeder_maps.{$configKey}.default");
            if (is_string($default) && $default !== '' && $fallback === null) {
                return $default;
            }
        }

        return $fallback;
    }

    public function resolveOrFail(string $category, string $siakadKey): string
    {
        $value = $this->resolve($category, $siakadKey);

        if ($value === null || $value === '') {
            throw new RuntimeException(
                "Belum ada pemetaan [{$category}] untuk kode Siakad [{$siakadKey}]. "
                .'Atur di menu Pemetaan Feeder.',
            );
        }

        return $value;
    }

    /**
     * @return list<FeederCodeMap>
     */
    public function listByCategory(string $category): array
    {
        return FeederCodeMap::query()
            ->where('category', $category)
            ->orderBy('siakad_key')
            ->get()
            ->all();
    }

    /**
     * @return array<string, list<FeederCodeMap>>
     */
    public function listGrouped(): array
    {
        $grouped = [];
        foreach (FeederCodeMap::categories() as $category) {
            $grouped[$category] = $this->listByCategory($category);
        }

        return $grouped;
    }

    public function feederLabel(string $category, string $feederValue): ?string
    {
        $ref = config("feeder_reference.{$category}");

        return is_array($ref) ? ($ref[$feederValue] ?? null) : null;
    }
}
