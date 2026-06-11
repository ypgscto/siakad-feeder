<?php

namespace App\Support\Sync;

class SyncLogModuleResolver
{
    /**
     * @return array<string, mixed>|null
     */
    public static function moduleConfig(?string $module): ?array
    {
        if ($module === null || $module === '') {
            return null;
        }

        $config = config("feeder_sync_log.modules.{$module}");

        if (is_array($config)) {
            return $config;
        }

        $logRoute = self::fallbackLogRoute($module);

        if ($logRoute === null) {
            return null;
        }

        return [
            'label' => ucfirst(str_replace('-', ' ', $module)),
            'index_route' => self::fallbackIndexRoute($module),
            'log_route' => $logRoute,
            'sync_types' => [],
        ];
    }

    public static function resolveFromSession(?string $explicitModule = null): ?string
    {
        $fromSession = session('sync_log_module');
        if (is_string($fromSession) && $fromSession !== '') {
            return $fromSession;
        }

        if (is_string($explicitModule) && $explicitModule !== '') {
            return $explicitModule;
        }

        $routeName = (string) (request()->route()?->getName() ?? '');
        foreach (array_keys(config('feeder_sync_log.modules', [])) as $module) {
            $prefix = 'admin.'.str_replace('-', '-', $module);
            if (str_starts_with($routeName, $prefix)) {
                return $module;
            }
        }

        foreach (self::fallbackModules() as $module => $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return $module;
            }
        }

        return null;
    }

    public static function shouldShowPanel(?string $explicitModule = null): bool
    {
        if (! session('success') && ! session('error')) {
            return false;
        }

        if (session('sync_result') || session('sync_log_module') || session('sync_failed_items')) {
            return true;
        }

        $message = (string) session('success').(string) session('error');

        return (bool) preg_match('/\d+\s+berhasil|\d+\s+gagal|Kirim |Update /i', $message);
    }

    /**
     * @return array<string, string>
     */
    public static function logQuery(): array
    {
        return array_filter([
            'status' => session('error') ? 'failed' : '',
            'date_from' => now()->format('Y-m-d'),
        ], fn ($value) => $value !== '');
    }

    /**
     * @return array<string, string>
     */
    protected static function fallbackModules(): array
    {
        return [
            'mahasiswa' => 'admin.mahasiswa.',
            'kelas' => 'admin.kelas.',
            'nilai' => 'admin.nilai.',
            'perkuliahan' => 'admin.perkuliahan.',
            'konversi-nilai' => 'admin.konversi-nilai.',
            'mahasiswa-keluar' => 'admin.mahasiswa-keluar.',
        ];
    }

    protected static function fallbackLogRoute(string $module): ?string
    {
        $route = 'admin.'.$module.'.log';

        return \Illuminate\Support\Facades\Route::has($route) ? $route : null;
    }

    protected static function fallbackIndexRoute(string $module): string
    {
        $route = 'admin.'.$module.'.index';

        return \Illuminate\Support\Facades\Route::has($route) ? $route : 'dashboard';
    }
}
