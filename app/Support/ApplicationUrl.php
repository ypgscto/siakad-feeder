<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;

class ApplicationUrl
{
    public static function apply(): void
    {
        $root = rtrim((string) config('app.url'), '/');
        if (! self::isValidHttpUrl($root)) {
            return;
        }

        URL::forceRootUrl($root);

        $asset = rtrim((string) (config('app.asset_url') ?: $root), '/');
        if (self::isValidHttpUrl($asset)) {
            URL::useAssetOrigin($asset);
        }
    }

    public static function isValidHttpUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return false;
        }

        if (str_contains($url, '\\') || preg_match('#://[A-Za-z]:/#', $url)) {
            return false;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
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
