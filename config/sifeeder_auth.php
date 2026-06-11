<?php

return [

    'login_endpoint' => env('SIFEEDER_AUTH_LOGIN_ENDPOINT', '/api/auth/login-app'),

    'login_password_hash_endpoint' => env('SIFEEDER_AUTH_LOGIN_PASSWORD_HASH_ENDPOINT', '/api/auth/login-password-hash'),

    'login_mysql_legacy_endpoint' => env('SIFEEDER_AUTH_LOGIN_MYSQL_LEGACY_ENDPOINT', '/api/auth/login-mysql-legacy'),

    /*
    | Cadangan login Siakad-GS (login-password-hash / login-mysql-legacy).
    | Default false — sama seperti SI-Tercapai/SIMAWA yang hanya memakai login-app.
    | Aktifkan hanya jika institusi masih pakai password legacy tabel karyawan.
    */
    'use_legacy_login_fallback' => (bool) env('SIFEEDER_USE_LEGACY_LOGIN_FALLBACK', false),

    /*
    | LevelID Siakad-GS (karyawan) dicoba jika legacy fallback aktif — 1=superadmin, 20=administrator.
    */
    'login_level_ids' => ['1', '20', '91'],

    'sso_lookup_endpoint' => env('SIFEEDER_SSO_LOOKUP_ENDPOINT', '/api/users/sso-lookup'),

    'kode_id' => env('SIAKAD_KODE_ID', ''),

    'allow_local_fallback' => (bool) env('SIFEEDER_ALLOW_LOCAL_LOGIN_FALLBACK', true),

    'email_domain' => (string) env('SIFEEDER_SIAKAD_EMAIL_DOMAIN', 'stikesgunungsari.ac.id'),

    'allowed_jenis_user' => ['6', '8', '9'],

    'denied_jenis_user' => ['0', '1'],

    'assignable_roles' => ['superadmin', 'admin', 'prodi'],

    'roles_requiring_prodi' => ['prodi'],

    'role_labels' => [
        'superadmin' => 'Superadmin',
        'admin' => 'Admin',
        'prodi' => 'Ketua Prodi',
    ],

];
