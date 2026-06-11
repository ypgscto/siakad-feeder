<?php

namespace App\Support\Siakad;

use RuntimeException;

final class SiakadConfig
{
    public static function baseUrl(): string
    {
        return (string) config('siakad.base_url', '');
    }

    public static function token(): string
    {
        return (string) config('siakad.token', '');
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
