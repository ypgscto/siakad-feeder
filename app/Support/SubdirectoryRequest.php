<?php

namespace App\Support;

/**
 * Normalisasi REQUEST_URI saat aplikasi di subfolder (mis. /siakad-feeder/public).
 */
class SubdirectoryRequest
{
    public static function applyFromEnvFile(string $envPath): bool
    {
        $base = self::readSubdirectoryFromEnv($envPath);
        if ($base === '') {
            return false;
        }

        self::apply($base);

        return true;
    }

    /**
     * Deteksi otomatis dari SCRIPT_NAME (Apache subfolder tanpa APP_SUBDIRECTORY).
     */
    public static function applyAutoDetect(): void
    {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if (! str_ends_with($scriptName, '/index.php')) {
            return;
        }

        $base = substr($scriptName, 0, -strlen('/index.php'));
        if ($base === '' || $base === '/') {
            return;
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        if (! str_starts_with($path, $base)) {
            return;
        }

        self::apply($base);
    }

    public static function apply(string $base): void
    {
        $base = '/'.trim($base, '/');
        if ($base === '/') {
            return;
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
        $query = parse_url($requestUri, PHP_URL_QUERY);

        if (! str_starts_with($path, $base)) {
            return;
        }

        $path = substr($path, strlen($base)) ?: '/';
        if ($path !== '/' && ! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }

        $_SERVER['REQUEST_URI'] = $path.($query ? '?'.$query : '');
        $_SERVER['SCRIPT_NAME'] = rtrim($base, '/').'/index.php';
        $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    }

    protected static function readSubdirectoryFromEnv(string $envPath): string
    {
        if (! is_readable($envPath)) {
            return '';
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return '';
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (! str_starts_with($line, 'APP_SUBDIRECTORY=')) {
                continue;
            }

            $value = trim(substr($line, strlen('APP_SUBDIRECTORY=')));

            return trim($value, " \t\"'");
        }

        return '';
    }
}
