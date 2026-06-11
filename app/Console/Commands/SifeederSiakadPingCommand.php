<?php

namespace App\Console\Commands;

use App\Services\SiakadApiService;
use App\Support\Siakad\SiakadConfig;
use App\Support\Siakad\SiakadResource;
use Illuminate\Console\Command;

class SifeederSiakadPingCommand extends Command
{
    protected $signature = 'sifeeder:siakad-ping';

    protected $description = 'Uji koneksi ke Siakad-API (health + sample prodi)';

    public function handle(SiakadApiService $api): int
    {
        $base = SiakadConfig::baseUrl();
        if ($base === '') {
            $this->error('SIAKAD_API_BASE_URL belum diatur.');

            return self::FAILURE;
        }

        $this->info("Base URL: {$base}");

        try {
            $health = $api->pingHealth();
            $this->line('Health: '.json_encode($health));
        } catch (\Throwable $e) {
            $this->error('Health gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        try {
            $rows = $api->fetchList(SiakadResource::STUDY_PROGRAMS);
            $this->info('Prodi: '.count($rows).' baris');
        } catch (\Throwable $e) {
            $this->error('Fetch prodi gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Siakad-API OK.');

        return self::SUCCESS;
    }
}
