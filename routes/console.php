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

// Bersihkan job gagal yang lama dan batch generate yang sudah selesai.
Schedule::command('queue:prune-failed --hours=720')->daily();
Schedule::command('queue:prune-batches --hours=168')->daily();
