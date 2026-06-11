<?php

namespace App\Providers;

use App\Services\IntegrationSettingsService;
use App\Services\SidebarMenu;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        try {
            if (Schema::hasTable('settings')) {
                app(IntegrationSettingsService::class)->applyToConfig();
            }
        } catch (\Throwable) {
            // Abaikan saat migrasi / bootstrap awal.
        }

        [$root, $assetRoot] = $this->resolveApplicationUrls();

        if ($root !== '') {
            URL::forceRootUrl($root);
        }

        if ($assetRoot !== '') {
            URL::useAssetOrigin($assetRoot);
        }

        View::composer('layouts.partials.sidebar-nav', function ($view): void {
            $view->with('sidebarMenu', app(SidebarMenu::class)->forUser(auth()->user()));
        });
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function resolveApplicationUrls(): array
    {
        $root = rtrim((string) config('app.url'), '/');
        $assetRoot = rtrim((string) (config('app.asset_url') ?: $root), '/');

        if ($this->app->runningInConsole()) {
            return [$root, $assetRoot];
        }

        $request = request();
        if ($request === null) {
            return [$root, $assetRoot];
        }

        $detected = rtrim($request->root(), '/');
        if ($detected === '' || ! str_starts_with($detected, 'http')) {
            return [$root, $assetRoot];
        }

        $root = $detected;
        if (blank(config('app.asset_url'))) {
            $assetRoot = $detected;
        }

        return [$root, $assetRoot];
    }
}
