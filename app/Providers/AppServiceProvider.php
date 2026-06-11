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

        if (! $this->app->runningInConsole()) {
            $request = $this->app->make('request');
            $detected = rtrim($request->root(), '/');
            $configured = rtrim((string) config('app.url'), '/');

            $root = ($detected !== '' && str_starts_with($detected, 'http'))
                ? $detected
                : $configured;

            if ($root !== '') {
                URL::forceRootUrl($root);
            }
        }

        View::composer('layouts.partials.sidebar-nav', function ($view): void {
            $view->with('sidebarMenu', app(SidebarMenu::class)->forUser(auth()->user()));
        });
    }
}
