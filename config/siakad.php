<?php

use App\Support\Siakad\SiakadResource;

/*
| SEMUA data akademik dibaca lewat endpoint di bawah (siakad-api → siakad_db).
| Siakad-Feeder tidak menyimpan salinan master akademik di database lokal.
*/

return [

    'base_url' => rtrim((string) env('SIAKAD_API_BASE_URL', ''), '/'),

    'token' => (string) env('SIAKAD_API_TOKEN', ''),

    'timeout' => (int) env('SIAKAD_API_TIMEOUT', 120),

    'api_host' => env('SIAKAD_API_HOST'),

    'endpoints' => [
        SiakadResource::HEALTH => env('SIAKAD_ENDPOINT_HEALTH', '/api/health'),
        SiakadResource::STUDY_PROGRAMS => env('SIAKAD_ENDPOINT_STUDY_PROGRAMS', '/api/prodi'),
        SiakadResource::PROGRAMS => env('SIAKAD_ENDPOINT_PROGRAMS', '/api/program'),
        SiakadResource::STATUS_AWAL => env('SIAKAD_ENDPOINT_STATUS_AWAL', '/api/status-awal'),
        SiakadResource::ACADEMIC_YEARS => env('SIAKAD_ENDPOINT_ACADEMIC_YEARS', '/api/semester-aktif'),
        SiakadResource::COHORTS => env('SIAKAD_ENDPOINT_COHORTS', '/api/angkatan-mahasiswa'),
        SiakadResource::MAHASISWA_SYNC => env('SIAKAD_ENDPOINT_MAHASISWA_SYNC', '/api/mahasiswa-sync'),
        SiakadResource::LECTURERS => env('SIAKAD_ENDPOINT_LECTURERS', '/api/dosen'),
        SiakadResource::KHS => env('SIAKAD_ENDPOINT_KHS', '/api/khs'),
        SiakadResource::CLASSES => env('SIAKAD_ENDPOINT_CLASSES', '/api/kelas'),
        SiakadResource::CLASS_PARTICIPANTS => env('SIAKAD_ENDPOINT_CLASS_PARTICIPANTS', '/api/kelas-peserta'),
        SiakadResource::GRADES => env('SIAKAD_ENDPOINT_GRADES', '/api/nilai'),
        SiakadResource::CONVERSION_GRADES => env('SIAKAD_ENDPOINT_CONVERSION_GRADES', '/api/nilai-konversi'),
        SiakadResource::STUDENT_EXIT => env('SIAKAD_ENDPOINT_STUDENT_EXIT', '/api/mahasiswa-keluar'),
        SiakadResource::GRADUATION_STATUS => env('SIAKAD_ENDPOINT_GRADUATION_STATUS', '/api/status-lulus'),
    ],

];
