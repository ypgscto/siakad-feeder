<?php

namespace App\Console\Commands;

use App\Services\Feeder\FeederClient;
use App\Services\SiakadApiService;
use App\Services\Sync\MahasiswaFeederService;
use App\Support\Feeder\HandphoneNormalizer;
use App\Support\Feeder\TanggalDaftarResolver;
use Illuminate\Console\Command;
use RuntimeException;

class SifeederPreviewBiodataCommand extends Command
{
    protected $signature = 'sifeeder:preview-biodata {nim : NIM mahasiswa}';

    protected $description = 'Pratinjau biodata yang akan dikirim ke Neo Feeder (termasuk nomor HP)';

    public function handle(SiakadApiService $siakad, MahasiswaFeederService $sync, FeederClient $feeder): int
    {
        $nim = (string) $this->argument('nim');

        try {
            $rows = $siakad->fetchMahasiswaSync(['nims' => $nim]);
        } catch (RuntimeException $e) {
            $this->error('Siakad-API: '.$e->getMessage());

            return self::FAILURE;
        }

        $student = collect($rows)->first(fn (array $row) => (string) ($row['nim'] ?? '') === $nim);

        if ($student === null) {
            $this->error("Mahasiswa NIM {$nim} tidak ditemukan di Siakad-API.");

            return self::FAILURE;
        }

        $rawHp = (string) ($student['handphone'] ?? '');
        $this->info("NIM: {$nim}");
        $this->line('Tahun masuk (id_periode_masuk): '.($student['tahun_id'] ?? '-'));
        $this->line('Tgl kuliah mulai Siakad: '.($student['tgl_kuliah_mulai'] ?? '(kosong)'));
        $this->line('Tanggal daftar ke Feeder: '.TanggalDaftarResolver::resolve($student));
        $this->line('HP dari Siakad-API: '.($rawHp !== '' ? $rawHp : '(kosong — update siakad-api jika seharusnya terisi)'));
        $this->line('Kandidat HP: '.implode(', ', HandphoneNormalizer::candidates($rawHp, $nim)));

        try {
            $record = $sync->buildBiodataRecord($student);
        } catch (RuntimeException $e) {
            $this->error('Gagal menyusun biodata: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Record InsertBiodataMahasiswa:');
        $this->line(json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $nik = (string) ($record['nik'] ?? '');
        if ($nik !== '') {
            try {
                $existing = $feeder->getList('GetBiodataMahasiswa', "nik = '".str_replace("'", '', $nik)."'", 1);
                if ($existing !== []) {
                    $this->warn('Biodata dengan NIK ini SUDAH ada di Feeder (riwayat belum = NIM belum muncul di daftar mahasiswa).');
                    $this->line('Gunakan tombol "Tambah Riwayat", bukan "Kirim Biodata + Riwayat".');
                }
            } catch (RuntimeException $e) {
                $this->warn('Cek Feeder gagal: '.$e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
