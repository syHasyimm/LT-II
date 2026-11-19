<?php

use Illuminate\Support\Facades\Route;

it('mengirim header keamanan di setiap halaman', function () {
    $this->get('/')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeaderMissing('Strict-Transport-Security');
});

it('tidak mendaftarkan login pembina demo di luar lingkungan lokal', function () {
    expect(app()->isLocal())->toBeFalse()
        ->and(Route::has('dev.login'))->toBeFalse();

    $this->get('/dev/login-pembina')->assertNotFound();
});

it('menyediakan endpoint health check untuk pemantauan', function () {
    $this->get('/up')->assertOk();
});
