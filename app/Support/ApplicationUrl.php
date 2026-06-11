<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;

class ApplicationUrl
{
    public static function apply(): void
    {
        $root = rtrim((string) config('app.url'), '/');
        if ($root === '' || ! str_starts_with($root, 'http')) {
            return;
        }

        URL::forceRootUrl($root);

        $asset = rtrim((string) (config('app.asset_url') ?: $root), '/');
        if ($asset !== '') {
            URL::useAssetOrigin($asset);
        }
    }

    public static function subdirectoryPrefix(): string
    {
        $subdir = trim((string) config('app.subdirectory', ''), '/');

        return $subdir === '' ? '' : '/'.$subdir;
    }

    public static function isUnderSubdirectory(string $url): bool
    {
        $prefix = self::subdirectoryPrefix();
        if ($prefix === '') {
            return true;
        }

        $path = parse_url($url, PHP_URL_PATH) ?? '';

        return $path === $prefix || str_starts_with($path, $prefix.'/');
    }
}
