<?php

namespace App\Support;

final class SettingsFieldName
{
    /**
     * Konversi key setting (siakad.api.base_url) ke name HTML (siakad[api][base_url]).
     * Tanpa ini, PHP mengubah titik menjadi underscore dan validasi Laravel gagal.
     */
    public static function html(string $key): string
    {
        $parts = explode('.', $key);
        $name = array_shift($parts) ?? '';

        foreach ($parts as $part) {
            $name .= '['.$part.']';
        }

        return $name;
    }
}
