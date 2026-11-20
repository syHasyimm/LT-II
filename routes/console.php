<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Jadwal tugas
|--------------------------------------------------------------------------
|
| Server produksi cukup menjalankan satu cron setiap menit:
|   * * * * * cd /var/www/perkemahan-lt2 && php artisan schedule:run >> /dev/null 2>&1
|
*/

// Backup database setiap malam, backup penuh (database + dokumen) setiap Minggu (BR-13).
Schedule::command('app:backup --db-only')->dailyAt('01:30')->timezone('Asia/Jakarta')->withoutOverlapping()->onOneServer();
Schedule::command('app:backup')->weeklyOn(0, '02:30')->timezone('Asia/Jakarta')->withoutOverlapping()->onOneServer();

// Queue worker untuk hosting bersama tanpa supervisor (mis. Hostinger): diproses
// tiap menit sampai antrean kosong. --max-time menjaga worker tidak jalan
// berlarut; kunci overlap kedaluwarsa 10 menit agar worker yang mati tidak
// memblokir antrean seharian. Di server dengan supervisor baris ini aman tetap
// ada, tetapi bisa dihapus.
Schedule::command('queue:work database --stop-when-empty --tries=3 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->runInBackground();

// Bersihkan job gagal yang lama dan batch generate yang sudah selesai.
Schedule::command('queue:prune-failed --hours=720')->daily();
Schedule::command('queue:prune-batches --hours=168')->daily();
