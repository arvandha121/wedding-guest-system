<?php

return [

    /*
    | Zona waktu untuk TAMPILAN. Data disimpan dalam UTC (config app.timezone).
    */
    'display_timezone' => env('WGS_DISPLAY_TIMEZONE', 'Asia/Jakarta'),

    /*
    | Masa berlaku token API (menit) per peran. Sanctum tidak punya refresh token,
    | jadi /auth/refresh merotasi token (token lama dicabut, token baru diterbitkan).
    */
    'token_ttl_minutes' => [
        'super_admin' => (int) env('WGS_TOKEN_TTL_ADMIN', 480),
        'event_admin' => (int) env('WGS_TOKEN_TTL_ADMIN', 480),
        'receptionist' => (int) env('WGS_TOKEN_TTL_RECEPTIONIST', 720),
    ],

    /*
    | Tema undangan yang tersedia (template dibuat pada Phase 4).
    */
    'themes' => ['classic', 'minimal', 'garden'],

    /*
    | Batas percobaan login per menit untuk kombinasi email + IP.
    */
    'login_attempts_per_minute' => (int) env('WGS_LOGIN_ATTEMPTS', 5),

    /*
    | Akun Super Admin awal untuk seeder. Wajib diisi lewat .env; tidak ada default.
    */
    'seed_admin' => [
        'name' => env('SEED_ADMIN_NAME', 'Super Admin'),
        'email' => env('SEED_ADMIN_EMAIL'),
        'password' => env('SEED_ADMIN_PASSWORD'),
    ],
];
