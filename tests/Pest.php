<?php

use App\Models\Setting;
use App\Services\RegistrationWindow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Tes Feature berbasis Pest memakai TestCase aplikasi dan database bersih.
| Secara bawaan pendaftaran sedang dibuka; tes jadwal mengatur ulang sendiri.
| Tes kelas PHPUnit bawaan starter kit tetap berjalan apa adanya.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => setRegistrationWindow(now()->subDays(10), now()->addDays(20)))
    ->in('Feature/Domain');

function setRegistrationWindow(?DateTimeInterface $opensAt, ?DateTimeInterface $closesAt): void
{
    Setting::set(RegistrationWindow::OPEN_KEY, $opensAt?->format(DATE_ATOM));
    Setting::set(RegistrationWindow::CLOSE_KEY, $closesAt?->format(DATE_ATOM));
}
