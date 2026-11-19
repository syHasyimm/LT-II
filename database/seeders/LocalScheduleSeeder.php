<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\RegistrationWindow;
use Illuminate\Database\Seeder;

/**
 * Jadwal contoh untuk pengembangan lokal saja. Di produksi jadwal diisi admin
 * lewat dashboard (FR-PRD-01), tanpa nilai tetap di kode.
 */
class LocalScheduleSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->isLocal() || Setting::get(RegistrationWindow::OPEN_KEY)) {
            return;
        }

        $timezone = (string) config('camp.timezone');

        Setting::set(RegistrationWindow::OPEN_KEY, now($timezone)->subDays(18)->setTime(8, 0)->toIso8601String());
        Setting::set(RegistrationWindow::CLOSE_KEY, now($timezone)->addDays(26)->setTime(23, 59)->toIso8601String());
    }
}
