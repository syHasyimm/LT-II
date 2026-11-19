<?php

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function fakeGoogleUser(string $id = '1234567890', string $email = 'rahmat@example.com', string $name = 'Rahmat Hidayat'): void
{
    $googleUser = (new SocialiteUser)->map([
        'id' => $id,
        'name' => $name,
        'email' => $email,
        'avatar' => 'https://lh3.googleusercontent.com/a/avatar',
    ]);

    Socialite::shouldReceive('driver->user')->andReturn($googleUser);
}

it('menampilkan halaman masuk pembina', function () {
    $this->get(route('pembina.login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('public/login'));
});

it('mengarahkan ke Google saat tombol masuk ditekan', function () {
    config()->set('services.google', [
        'client_id' => 'test-client',
        'client_secret' => 'test-secret',
        'redirect' => 'http://localhost/auth/google/callback',
    ]);

    $this->get(route('auth.google'))
        ->assertRedirectContains('accounts.google.com');
});

it('membuat akun pembina pada login Google pertama', function () {
    fakeGoogleUser();

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('pembina.dashboard'));

    $user = User::query()->sole();

    expect($user->role)->toBe(UserRole::Pembina)
        ->and($user->google_id)->toBe('1234567890')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->password)->toBeNull();

    $this->assertAuthenticatedAs($user);
});

it('masuk ke akun yang sama pada login berikutnya', function () {
    $existing = User::factory()->pembina()->create(['google_id' => '1234567890']);
    fakeGoogleUser(email: 'email-baru@example.com');

    $this->get(route('auth.google.callback'));

    expect(User::query()->count())->toBe(1);
    $this->assertAuthenticatedAs($existing);
});

it('menolak akun Google yang emailnya terdaftar sebagai admin', function () {
    User::factory()->admin()->create(['email' => 'panitia@example.com']);
    fakeGoogleUser(email: 'panitia@example.com');

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('pembina.login'))
        ->assertSessionHasErrors('google');

    $this->assertGuest();
});

it('tidak mendaftarkan rute dev-login di luar lingkungan lokal', function () {
    $this->get('/dev/login-pembina')->assertNotFound();
});

it('mengarahkan tamu di area pembina ke halaman masuk pembina', function () {
    $this->get(route('pembina.dashboard'))->assertRedirect(route('pembina.login'));
});
