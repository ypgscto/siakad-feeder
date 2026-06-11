<?php

/*
| Referensi master kode Siakad-GS (bukan Feeder).
| Dipakai label di halaman Pemetaan Feeder & seeder feeder_code_maps.
*/

return [

    /*
    | Tabel statusawal — status masuk / jenis pendaftaran mahasiswa
    */
    'statusawal' => [
        'P' => 'Pindahan',
        'B' => 'Baru',
        'S' => 'Beasiswa',
        'D' => 'Drop-in',
        'J' => 'Alih Jenjang/RPL',
        'M' => 'Mitra',
    ],

    /*
    | Tabel statusmhsw — status akademik mahasiswa saat ini
    */
    'statusmhsw' => [
        'A' => 'Aktif',
        'C' => 'Cuti',
        'P' => 'Pasif',
        'K' => 'Keluar',
        'D' => 'Drop-out (DO)',
        'L' => 'Lulus',
        'T' => 'Tunggu Ujian',
        'W' => 'Tunggu Wisuda',
        'S' => 'Skorsing',
    ],

    /*
    | Catatan pemakaian di jembatan Feeder
    */
    'usage_notes' => [
        'statusawal' => 'Dipetakan ke id_jenis_daftar saat Insert/Update Riwayat Pendidikan Mahasiswa.',
        'statuslulus' => 'Dipetakan ke id_jenis_keluar saat InsertMahasiswaLulusDO (kolom ta.StatusLulusID — biasanya selaras kode L/D dengan statusmhsw).',
        'statusmhsw' => 'Dipetakan ke id_status_mahasiswa saat InsertPerkuliahanMahasiswa (aktivitas kuliah per semester).',
    ],

];
