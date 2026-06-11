<?php

/*
| Konfigurasi PENDUKUNG jembatan Neo Feeder (bukan master akademik Siakad).
| - prodi[] : seed awal untuk tabel feeder_prodi_maps (Siakad ProdiID → UUID Feeder).
| - agama, jenis_daftar : pemetaan kode saat kirim WS.
| Master prodi/mahasiswa tetap dari siakad-api.
*/

return [

    'id_perguruan_tinggi' => env('FEEDER_ID_PERGURUAN_TINGGI', '2c56ef95-0194-410b-a7a6-704471b27452'),

    'id_perguruan_tinggi_pindahan' => env('FEEDER_ID_PT_PINDAHAN', 'd5341262-e79d-4e4e-a4eb-cbffcc792d73'),

    'id_perguruan_tinggi_rpl' => env('FEEDER_ID_PT_RPL', '3f9395b3-8997-4013-9d94-d7c3b7fecbc4'),

    'id_jalur_daftar' => '12',

    'id_pembiayaan' => '1',

    'biaya_masuk' => '0',

    'default_email' => env('FEEDER_DEFAULT_EMAIL', 'yayasanpendidikan.gunungsari@gmail.com'),

    'default_nisn' => '0000000000',

    'default_handphone' => env('FEEDER_DEFAULT_HANDPHONE', ''),

    /*
    | Fallback tanggal_daftar jika TglKuliahMulai tidak ada di Siakad.
    | TahunID genap (…2/…4) → Februari tahun berikutnya; ganjil → September.
    */
    'tanggal_daftar' => [
        'ganjil' => env('FEEDER_TGL_DAFTAR_GANJIL', '09-01'),
        'genap' => env('FEEDER_TGL_DAFTAR_GENAP', '02-01'),
        'genap_year_offset' => (int) env('FEEDER_TGL_DAFTAR_GENAP_YEAR_OFFSET', 1),
    ],

    'default_kelurahan' => 'Gunung Sari',

    'default_kecamatan' => 'Kec. Rappocini - Kota Makassar - Prov. Sulawesi Selatan',

    'default_kewarganegaraan' => 'ID',

    'perkuliahan' => [
        'id_status_mahasiswa' => 'A ',
        'biaya_kuliah_smt' => '0',
    ],

    'kelas' => [
        'rencana_minggu' => '16',
        'rencana_tatap_muka' => '16',
        'realisasi_tatap_muka' => '16',
        'id_jenis_evaluasi' => '1',
        'default_sks_pengajar' => '2',
    ],

    /*
    | Map StatusAwalID (tabel statusawal) → id_jenis_daftar Feeder
    | B=Baru, P=Pindahan, J=RPL, S=Beasiswa, D=Drop-in, M=Mitra
    */
    'jenis_daftar' => [
        'B' => '1',
        'P' => '2',
        'J' => '16',
        'S' => '1',
        'D' => '1',
        'M' => '1',
    ],

    /*
    | Map StatusLulusID / status keluar (ta.StatusLulusID, selaras statusmhsw L/D/K)
    | → id_jenis_keluar Feeder (PDDikti)
    */
    'jenis_keluar' => [
        'default' => '1',
        'L' => '1',
        'D' => '3',
        'K' => '4',
    ],

    /*
    | Map StatusMhswID (tabel statusmhsw) → id_status_mahasiswa Feeder (aktivitas kuliah)
    */
    'status_mahasiswa' => [
        'default' => 'A ',
        'A' => 'A ',
        'C' => 'C',
        'P' => 'N',
        'K' => 'N',
        'D' => 'N',
        'L' => 'N',
        'T' => 'A ',
        'W' => 'A ',
        'S' => 'C',
    ],

    /*
    | Map nama agama (Siakad) → id_agama Feeder
    */
    'kelamin' => [
        'Pria' => 'L',
        'Wanita' => 'P',
        'Laki-laki' => 'L',
        'Perempuan' => 'P',
    ],

    'agama' => [
        'Islam' => '1',
        'Kristen' => '2',
        'Katholik' => '3',
        'Hindu' => '4',
        'Budha' => '5',
        'Konghucu' => '6',
        'Lain2' => '99',
        'Lain-lain' => '99',
    ],

    /*
    | Map ProdiID Siakad → UUID Feeder
    | prodi_asal: untuk pindahan (Jenis 2)
    | prodi_rpl: untuk RPL (Jenis 16)
    */
    'prodi' => [
        'NERS' => [
            'id_prodi' => '26ddeb7b-0f55-4ee4-a390-b0634b670e6d',
        ],
        'ILMU KEPERAWATAN' => [
            'id_prodi' => '8d1965a3-1040-4dff-a251-4b2848dd8418',
            'prodi_asal' => '7cad2382-7f0a-4c9f-9afd-0960a6c5fee4',
            'prodi_rpl' => 'f842d53b-080d-4add-b371-d75edc8b7b02',
        ],
        'D3 Kebidanan' => [
            'id_prodi' => 'ad07174e-32e8-4447-a886-1ebf4591369e',
            'prodi_asal' => 'ec96dc6a-f419-4833-a589-93aa0e53ea46',
        ],
        'KEPERAWATAN' => [
            'id_prodi' => '9fd45375-889d-43dd-b75a-db8146d2e4b0',
        ],
        'S1 Kebidanan' => [
            'id_prodi' => '034c0536-027f-43e5-8b6c-5c6f4eea9e72',
        ],
        'Profesi Bidan' => [
            'id_prodi' => '6f398b19-5ce9-4ae3-9bf7-2c8c3b0b38b1',
        ],
    ],

];
