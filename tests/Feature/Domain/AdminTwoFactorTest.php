<?php

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

/** FR-AUTH-05: 2FA opsional untuk admin lewat Fortify. */
function adminWithTotp(): array
{
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $admin = User::factory()->admin()->create([
        'two_factor_secret' => encrypt($secret),
        'two_factor_recovery_codes' => encrypt(json_encode(['kode-cadangan-1', 'kode-cadangan-2'])),
        'two_factor_confirmed_at' => now(),
    ]);

    return [$admin, $google2fa->getCurrentOtp($secret)];
}

it('meminta kode 2FA setelah kata sandi admin benar, lalu masuk ke dashboard admin', function () {
    [$admin, $code] = adminWithTotp();

    $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('two-factor.login'));
    $this->assertGuest();

    $this->get(route('two-factor.login'))
        ->assertInertia(fn ($page) => $page->component('auth/two-factor-challenge'));

    $this->post(route('two-factor.login.store'), ['code' => $code])
        ->assertRedirect(route('admin.dashboard', absolute: false));
    $this->assertAuthenticatedAs($admin);
});

it('menerima kode cadangan sekali pakai', function () {
    [$admin] = adminWithTotp();

    $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password']);
    $this->post(route('two-factor.login.store'), ['recovery_code' => 'kode-cadangan-1'])
        ->assertRedirect(route('admin.dashboard', absolute: false));

    expect($admin->refresh()->recoveryCodes())->not->toContain('kode-cadangan-1');
});

it('menolak kode 2FA yang salah', function () {
    [$admin] = adminWithTotp();

    $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password']);
    $this->post(route('two-factor.login.store'), ['code' => '000000'])
        ->assertRedirect(route('two-factor.login'))
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});

it('mengingatkan admin yang belum mengaktifkan 2FA di dashboard', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->where('twoFactorEnabled', false));

    [$secured] = adminWithTotp();
    $this->actingAs($secured)
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->where('twoFactorEnabled', true));
});
