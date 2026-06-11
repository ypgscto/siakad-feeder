<?php

namespace App\Support\Feeder;

/**
 * Ringkasan mapping hardcoded di aplikasi lama sifeeder2 (CodeIgniter).
 */
final class Sifeeder2MappingScan
{
    /**
     * @return list<array{
     *   group: string,
     *   siakad_field: string,
     *   feeder_field: string,
     *   ci_value: string,
     *   feeder_value: string,
     *   source_files: list<string>,
     *   notes: string
     * }>
     */
    public static function findings(): array
    {
        return [
            [
                'group' => 'Prodi',
                'siakad_field' => 'ProdiID',
                'feeder_field' => 'id_prodi (UUID)',
                'ci_value' => 'NERS, ILMU KEPERAWATAN, D3 Kebidanan, …',
                'feeder_value' => 'UUID per prodi (switch/mapProdiToUUID)',
                'source_files' => ['Sifeeder.php', 'List_Kelas_Perkuliahan.php', 'Mata_Kuliah.php', 'Kurikulum.php'],
                'notes' => 'Juga prodi_asal & prodi_rpl untuk pindahan/RPL di Sifeeder.php',
            ],
            [
                'group' => 'Status Lulus / DO',
                'siakad_field' => 'StatusLulusID (ta) / statusmhsw',
                'feeder_field' => 'id_jenis_keluar',
                'ci_value' => 'Selalu "1" (tidak dipetakan per status)',
                'feeder_value' => 'L→1, D (DO)→3, K→4 (Laravel baru)',
                'source_files' => ['Daftar_Mahasiswa_Lulus.php'],
                'notes' => 'Siakad-GS: DO = kode D (Drop-out), Lulus = L',
            ],
            [
                'group' => 'Status Awal Masuk',
                'siakad_field' => 'StatusAwalID (statusawal)',
                'feeder_field' => 'id_jenis_daftar',
                'ci_value' => 'B→1, P→2, J→16 saja',
                'feeder_value' => '+ S/D/M → 1 (Laravel)',
                'source_files' => ['Sifeeder.php'],
                'notes' => 'P=Pindahan, B=Baru, J=RPL, S=Beasiswa, D=Drop-in, M=Mitra',
            ],
            [
                'group' => 'Status Mahasiswa',
                'siakad_field' => 'StatusMhswID (statusmhsw)',
                'feeder_field' => 'id_status_mahasiswa',
                'ci_value' => 'Selalu "A " untuk aktivitas kuliah',
                'feeder_value' => 'A→A , C→C, D/DO→N, …',
                'source_files' => ['Perkuliahan.php'],
                'notes' => 'Tabel statusmhsw: A Aktif, D Drop-out, L Lulus, dll.',
            ],
            [
                'group' => 'Agama',
                'siakad_field' => 'Agama (nama)',
                'feeder_field' => 'id_agama',
                'ci_value' => 'Islam→1, Kristen→2, Katholik→3, Hindu→4, Budha→5, Konghucu→6, Lain2→99',
                'feeder_value' => '1–6, 99',
                'source_files' => ['Sifeeder.php', 'Bobotnilai.php (hanya Islam/lain)'],
                'notes' => 'Bobotnilai.php versi disederhanakan (Islam vs lainnya)',
            ],
            [
                'group' => 'Kelamin',
                'siakad_field' => 'Kelamin',
                'feeder_field' => 'jenis_kelamin',
                'ci_value' => 'Pria → L, selainnya → P',
                'feeder_value' => 'L / P',
                'source_files' => ['Sifeeder.php', 'Bobotnilai.php'],
                'notes' => '',
            ],
            [
                'group' => 'Perguruan Tinggi',
                'siakad_field' => '(jenis daftar)',
                'feeder_field' => 'id_perguruan_tinggi / id_perguruan_tinggi_asal',
                'ci_value' => 'Normal / pindahan / RPL → UUID berbeda',
                'feeder_value' => '2c56ef95… / d5341262… / 3f9395b3…',
                'source_files' => ['Sifeeder.php'],
                'notes' => 'Di Laravel: env FEEDER_ID_PERGURUAN_TINGGI*',
            ],
            [
                'group' => 'Aktivitas Kuliah',
                'siakad_field' => '(semua mahasiswa)',
                'feeder_field' => 'id_status_mahasiswa',
                'ci_value' => '"A " (Aktif + spasi)',
                'feeder_value' => 'A ',
                'source_files' => ['Perkuliahan.php'],
                'notes' => '',
            ],
            [
                'group' => 'Aktivitas Kuliah',
                'siakad_field' => '(semua)',
                'feeder_field' => 'id_pembiayaan, biaya_kuliah_smt',
                'ci_value' => '1, 0',
                'feeder_value' => '1 / 0',
                'source_files' => ['Perkuliahan.php', 'Sifeeder.php'],
                'notes' => '',
            ],
            [
                'group' => 'Riwayat Pendidikan',
                'siakad_field' => '(semua)',
                'feeder_field' => 'id_jalur_daftar, id_pembiayaan, biaya_masuk',
                'ci_value' => '12, 1, 0',
                'feeder_value' => '12 / 1 / 0',
                'source_files' => ['Sifeeder.php', 'Bobotnilai.php'],
                'notes' => '',
            ],
            [
                'group' => 'Kelas Kuliah',
                'siakad_field' => '(semua kelas)',
                'feeder_field' => 'id_jenis_evaluasi, SKS pengajar',
                'ci_value' => '1, 2',
                'feeder_value' => '1 / 2',
                'source_files' => ['Kelas_Perkuliahan.php'],
                'notes' => '',
            ],
            [
                'group' => 'Mata Kuliah',
                'siakad_field' => '(semua MK)',
                'feeder_field' => 'id_jenis_mata_kuliah',
                'ci_value' => '"A"',
                'feeder_value' => 'A',
                'source_files' => ['Mata_Kuliah.php'],
                'notes' => 'Belum dimigrasi ke Laravel',
            ],
            [
                'group' => 'Biodata default',
                'siakad_field' => '(fallback)',
                'feeder_field' => 'kelurahan, kecamatan, email, nisn',
                'ci_value' => 'Gunung Sari, Rappocini, email yayasan, Propinsi/000…',
                'feeder_value' => 'config feeder_maps defaults',
                'source_files' => ['Sifeeder.php', 'Kirim.php', 'Bobotnilai.php'],
                'notes' => '',
            ],
        ];
    }
}
