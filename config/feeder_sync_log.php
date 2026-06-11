<?php

return [

    'modules' => [
        'mahasiswa' => [
            'label' => 'Data Mahasiswa',
            'index_route' => 'admin.mahasiswa.index',
            'log_route' => 'admin.mahasiswa.log',
            'sync_types' => [
                'InsertBiodataMahasiswa',
                'InsertRiwayatPendidikanMahasiswa',
                'UpdateRiwayatPendidikanMahasiswa',
                'mahasiswa_biodata_riwayat',
                'mahasiswa_riwayat',
                'mahasiswa_riwayat_update',
            ],
        ],
        'kelas' => [
            'label' => 'Kelas Perkuliahan',
            'index_route' => 'admin.kelas.index',
            'log_route' => 'admin.kelas.log',
            'sync_types' => [
                'insert_kelas_kuliah',
                'insert_peserta_kelas',
                'insert_dosen_pengajar',
            ],
        ],
        'nilai' => [
            'label' => 'Nilai Perkuliahan',
            'index_route' => 'admin.nilai.index',
            'log_route' => 'admin.nilai.log',
            'sync_types' => [
                'update_nilai_kelas',
            ],
        ],
        'perkuliahan' => [
            'label' => 'Aktivitas Kuliah',
            'index_route' => 'admin.perkuliahan.index',
            'log_route' => 'admin.perkuliahan.log',
            'sync_types' => [
                'perkuliahan_mahasiswa',
            ],
        ],
        'konversi-nilai' => [
            'label' => 'Konversi Nilai',
            'index_route' => 'admin.konversi-nilai.index',
            'log_route' => 'admin.konversi-nilai.log',
            'sync_types' => [
                'nilai_konversi',
            ],
        ],
        'mahasiswa-keluar' => [
            'label' => 'Mahasiswa Lulus/DO',
            'index_route' => 'admin.mahasiswa-keluar.index',
            'log_route' => 'admin.mahasiswa-keluar.log',
            'sync_types' => [
                'mahasiswa_lulus_do',
            ],
        ],
    ],

    'sync_type_labels' => [
        'InsertBiodataMahasiswa' => 'Insert Biodata Mahasiswa',
        'InsertRiwayatPendidikanMahasiswa' => 'Insert Riwayat Pendidikan',
        'UpdateRiwayatPendidikanMahasiswa' => 'Update Riwayat Pendidikan',
        'mahasiswa_biodata_riwayat' => 'Biodata + Riwayat (berhasil)',
        'mahasiswa_riwayat' => 'Riwayat Pendidikan (berhasil)',
        'mahasiswa_riwayat_update' => 'Update Riwayat (berhasil)',
        'insert_kelas_kuliah' => 'Insert Kelas Kuliah',
        'insert_peserta_kelas' => 'Insert Peserta Kelas',
        'insert_dosen_pengajar' => 'Insert Dosen Pengajar',
        'update_nilai_kelas' => 'Update Nilai Kelas',
        'perkuliahan_mahasiswa' => 'Aktivitas Perkuliahan Mahasiswa',
        'nilai_konversi' => 'Nilai Konversi',
        'mahasiswa_lulus_do' => 'Mahasiswa Lulus/DO',
    ],

];
