<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kuota anggota per gudep
    |--------------------------------------------------------------------------
    |
    | BR-02: setiap gudep terdiri dari tepat 8 peserta Putra dan 8 peserta
    | Putri. BR-03: pembina pendamping minimal 1, tanpa batas maksimal.
    |
    */

    'peserta_per_regu' => 8,
    'groups' => ['pa', 'pi'],
    'pembina_min' => 1,
    'pembina_max' => null, // null = tanpa batas

    /*
    |--------------------------------------------------------------------------
    | Unggahan dokumen dan foto
    |--------------------------------------------------------------------------
    */

    'max_upload_kb' => 2048,
    'allowed_mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
    'photo_mimes' => ['jpg', 'jpeg', 'png'],
    'private_disk' => 'local',

    /*
    |--------------------------------------------------------------------------
    | Nomor seri, ID Card, dan Sertifikat
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Admin awal (dipakai AdminSeeder)
    |--------------------------------------------------------------------------
    */

    'seed_admin' => [
        'name' => env('ADMIN_NAME', 'Admin Panitia'),
        'email' => env('ADMIN_EMAIL', 'admin@perkemahan-lt2.test'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    'event_year' => 2026,
    'serial_prefix' => 'LT2',
    'timezone' => 'Asia/Jakarta',
    'id_card_mm' => ['width' => 55, 'height' => 85],
    'certificate_mm' => ['width' => 297, 'height' => 210],

];
