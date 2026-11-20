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

    // Dokumen gabungan (KTA, izin, sehat seluruh anggota dalam 1 PDF) butuh batas lebih besar.
    'max_upload_kb' => 10240,
    'max_photo_kb' => 2048,
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

    /*
    |--------------------------------------------------------------------------
    | Kop Formulir Pendaftaran (PDF admin). Logo: timpa file di public/images.
    |--------------------------------------------------------------------------
    */

    'kop' => [
        'title' => 'GERAKAN PRAMUKA',
        'subtitle' => 'KWARTIR RANTING KEPENUHAN',
        'address' => 'Jalan Syech Abdul Wahab Rokan-Kota Tengah No. Tlp.- Kodepos: 28559',
        'email' => 'Email : kwartirranting03kepenuhan@gmail.com',
        'logo_left' => 'images/kop-kiri.png',
        'logo_right' => 'images/kop-kanan.png',
    ],

    'event_year' => 2026,
    'serial_prefix' => 'LT2',
    'timezone' => 'Asia/Jakarta',
    'id_card_mm' => ['width' => 55, 'height' => 85],
    'certificate_mm' => ['width' => 297, 'height' => 210],

    /*
    |--------------------------------------------------------------------------
    | Backup terjadwal (BR-13, kebutuhan keandalan)
    |--------------------------------------------------------------------------
    |
    | `php artisan app:backup` membuat ZIP berisi dump database dan folder
    | dokumen privat di storage/app/backups. Jadwalnya ada di routes/console.php.
    | Salin folder backup ke lokasi lain (server/penyimpanan cloud) secara rutin.
    |
    */

    'backup' => [
        'path' => env('BACKUP_PATH', storage_path('app/backups')),
        'connection' => env('BACKUP_DB_CONNECTION'), // null = koneksi bawaan
        'keep_daily' => (int) env('BACKUP_KEEP_DAILY', 14),
        'keep_full' => (int) env('BACKUP_KEEP_FULL', 4),
        'mysqldump' => env('BACKUP_MYSQLDUMP', 'mysqldump'),
    ],

];
