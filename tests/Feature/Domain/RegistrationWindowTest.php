<?php

use App\Models\Setting;
use App\Services\RegistrationWindow;
use Carbon\CarbonImmutable;

function setSchedule(?string $open, ?string $close): void
{
    Setting::set(RegistrationWindow::OPEN_KEY, $open);
    Setting::set(RegistrationWindow::CLOSE_KEY, $close);
}

it('berstatus belum dibuka bila jadwal belum diisi', function () {
    setRegistrationWindow(null, null);

    expect(app(RegistrationWindow::class)->state())->toBe('not_open');
});

it('menghitung status berdasarkan waktu sekarang tanpa penjadwal', function (string $now, string $expected) {
    setSchedule('2026-11-01T08:00:00+07:00', '2026-12-15T23:59:00+07:00');

    expect(app(RegistrationWindow::class)->state(CarbonImmutable::parse($now)))->toBe($expected);
})->with([
    'sebelum buka' => ['2026-11-01T07:59:59+07:00', 'not_open'],
    'tepat buka' => ['2026-11-01T08:00:00+07:00', 'open'],
    'masa pendaftaran' => ['2026-11-19T10:00:00+07:00', 'open'],
    'tepat tutup' => ['2026-12-15T23:59:00+07:00', 'closed'],
]);

it('memformat label tanggal dalam bahasa Indonesia dan WIB', function () {
    setSchedule('2026-11-01T08:00:00+07:00', '2026-12-15T23:59:00+07:00');

    $data = app(RegistrationWindow::class)->toArray();

    expect($data['opens_label'])->toBe('1 Nov 2026, 08.00 WIB')
        ->and($data['closes_label'])->toBe('15 Des 2026, 23.59 WIB');
});

it('membagikan jadwal ke beranda publik', function () {
    setSchedule('2026-11-01T08:00:00+07:00', '2026-12-15T23:59:00+07:00');

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('welcome')
            ->where('rules.pesertaPerRegu', 8)
            ->where('rules.maxUploadMb', 10)
            ->where('rules.maxPhotoMb', 2)
            ->where('registration.closes_label', '15 Des 2026, 23.59 WIB'));
});
