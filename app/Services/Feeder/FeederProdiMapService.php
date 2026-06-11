<?php

namespace App\Services\Feeder;

use App\Models\FeederProdiMap;
use RuntimeException;

/**
 * Resolusi UUID prodi Feeder dari Siakad ProdiID (tabel pendukung lokal).
 */
class FeederProdiMapService
{
    /**
     * @return array{id_prodi: string, prodi_asal?: string, prodi_rpl?: string}
     */
    public function resolve(string $siakadProdiId): array
    {
        $siakadProdiId = trim($siakadProdiId);
        if ($siakadProdiId === '') {
            throw new RuntimeException('ProdiID Siakad kosong.');
        }

        $row = FeederProdiMap::query()
            ->where('siakad_prodi_id', $siakadProdiId)
            ->where('is_active', true)
            ->first();

        if ($row) {
            return array_filter([
                'id_prodi' => $row->feeder_id_prodi,
                'prodi_asal' => $row->feeder_id_prodi_asal,
                'prodi_rpl' => $row->feeder_id_prodi_rpl,
            ], fn ($v) => $v !== null && $v !== '');
        }

        $config = config("feeder_maps.prodi.{$siakadProdiId}");
        if (is_array($config) && ! empty($config['id_prodi'])) {
            return $config;
        }

        throw new RuntimeException(
            "ProdiID [{$siakadProdiId}] belum dipetakan ke UUID Feeder. "
            .'Tambahkan di tabel feeder_prodi_maps atau config/feeder_maps.php.',
        );
    }
}
