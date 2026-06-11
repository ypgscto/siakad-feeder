<?php

/*
|--------------------------------------------------------------------------
| Prinsip arsitektur Siakad-Feeder
|--------------------------------------------------------------------------
|
| - Data akademik (prodi, program, mahasiswa, dosen, nilai, dll.) SELALU
|   dibaca runtime dari siakad-api — TIDAK disimpan / disinkron ke DB lokal.
| - DB lokal hanya: users (auth aplikasi), feeder_sync_logs, feeder_prodi_maps,
|   cache/session/jobs Laravel.
| - feeder_prodi_maps = pemetaan pendukung Siakad ProdiID → UUID Neo Feeder,
|   BUKAN master program studi.
|
*/

return [

    'academic_source' => 'siakad-api',

];
