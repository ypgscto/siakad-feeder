<?php

namespace App\Console\Commands;

use App\Services\Feeder\FeederClient;
use Illuminate\Console\Command;

class SifeederFeederPingCommand extends Command
{
    protected $signature = 'sifeeder:feeder-ping';

    protected $description = 'Uji koneksi ke Neo Feeder (GetToken)';

    public function handle(FeederClient $feeder): int
    {
        $url = (string) config('feeder.ws_url');
        if ($url === '') {
            $this->error('FEEDER_WS_URL belum diatur.');

            return self::FAILURE;
        }

        $this->info("URL: {$url}");
        $this->info('User: '.config('feeder.username'));

        try {
            $feeder->ping();
            $this->info('Neo Feeder OK (token didapat).');
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
