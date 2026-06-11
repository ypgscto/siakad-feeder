<?php

namespace App\Console\Commands;

use App\Services\IntegrationSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SifeederSyncSettingsFromEnvCommand extends Command
{
    protected $signature = 'sifeeder:sync-settings-from-env {--force : Timpa nilai di database settings}';

    protected $description = 'Sinkronkan pengaturan koneksi dari .env ke database (perbaiki URL API salah)';

    public function handle(IntegrationSettingsService $settings): int
    {
        if (! $this->option('force')) {
            $this->warn('Gunakan --force untuk menimpa settings di database dari .env');
        }

        Artisan::call('config:clear');
        Artisan::call('cache:clear');

        $settings->syncFromEnv($this->option('force'));
        $settings->applyToConfig();

        $this->info('Base URL Siakad-API: '.config('siakad.base_url'));
        $this->info('Token terisi: '.(config('siakad.token') !== '' ? 'ya' : 'tidak'));
        $this->newLine();
        $this->line('Tes: php artisan sifeeder:siakad-ping');

        return self::SUCCESS;
    }
}
