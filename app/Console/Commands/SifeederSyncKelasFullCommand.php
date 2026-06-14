<?php

namespace App\Console\Commands;

use App\Services\Sync\KelasSemesterSyncService;
use Illuminate\Console\Command;

class SifeederSyncKelasFullCommand extends Command
{
    protected $signature = 'sifeeder:sync-kelas-full
                            {prodi_id : ProdiID Siakad, mis. "ILMU KEPERAWATAN"}
                            {tahun_id : ID semester Siakad, mis. 20252}';

    protected $description = 'Kirim kelas + peserta + dosen pengajar ke Neo Feeder untuk satu prodi/semester';

    public function handle(KelasSemesterSyncService $sync): int
    {
        @set_time_limit(900);

        $prodiId = (string) $this->argument('prodi_id');
        $tahunId = (string) $this->argument('tahun_id');

        $this->info("Sinkron penuh: {$prodiId} · {$tahunId}");

        try {
            $result = $sync->syncFull($prodiId, $tahunId);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($success = $result->flashSuccess()) {
            $this->info($success);
        }
        if ($error = $result->flashError()) {
            $this->warn($error);
        }

        $this->line("Selesai: {$result->successCount} OK, {$result->failedCount} gagal.");

        return $result->failedCount > 0 && $result->successCount === 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
