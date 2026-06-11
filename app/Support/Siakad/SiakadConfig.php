<?php

namespace App\Support\Siakad;

use RuntimeException;

final class SiakadConfig
{
    public static function baseUrl(): string
    {
        $url = rtrim(trim((string) config('siakad.base_url', '')), '/');

        if ($url === '') {
            return '';
        }

        if (str_contains($url, '\\') || preg_match('#^[A-Za-z]:[/\\\\]#', $url)) {
            return '';
        }

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return '';
        }

        return $url;
    }

    public static function token(): string
    {
        return trim((string) config('siakad.token', ''));
    }

    public static function endpointPath(string $resourceKey): string
    {
        $path = config("siakad.endpoints.{$resourceKey}");

        if (! is_string($path) || $path === '') {
            throw new RuntimeException(
                "Endpoint Siakad-API untuk [{$resourceKey}] belum dikonfigurasi di config/siakad.php.",
            );
        }

        if (! str_starts_with($path, '/')) {
            throw new RuntimeException(
                "Endpoint [{$resourceKey}] harus diawali '/' (nilai saat ini: {$path}).",
            );
        }

        return $path;
    }

    public static function fullUrl(string $resourceKey): string
    {
        return rtrim(self::baseUrl(), '/').self::endpointPath($resourceKey);
    }
}
