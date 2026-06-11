<?php

namespace App\Providers;

use App\Services\IntegrationSettingsService;
use App\Services\SidebarMenu;
use App\Support\ApplicationUrl;
use Illuminate\Support\Facades\Schema;
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

        ApplicationUrl::apply();

        View::composer('layouts.partials.sidebar-nav', function ($view): void {
            $view->with('sidebarMenu', app(SidebarMenu::class)->forUser(auth()->user()));
        });
    }
}
