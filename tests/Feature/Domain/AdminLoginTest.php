<?php

use App\Enums\UserRole;
use App\Models\User;

it('menampilkan halaman masuk admin di /admin/login', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/login'));
});

it('mengizinkan admin masuk dengan email dan kata sandi', function () {
    $admin = User::factory()->admin()->create();

    $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard', absolute: false));

    $this->assertAuthenticatedAs($admin);
});

it('menolak pembina masuk lewat form admin walau kata sandinya benar', function () {
    $pembina = User::factory()->create(['role' => UserRole::Pembina]);

    $this->post(route('login.store'), ['email' => $pembina->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('menonaktifkan pendaftaran akun publik', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', [])->assertNotFound();
});

it('membuat admin lewat perintah artisan', function () {
    $this->artisan('app:create-admin', [
        '--name' => 'Admin Panitia',
        '--email' => 'Panitia@Contoh.id',
        '--password' => 'rahasia-kuat-123',
    ])->assertSuccessful();

    $admin = User::query()->where('email', 'panitia@contoh.id')->sole();

    expect($admin->isAdmin())->toBeTrue()
        ->and($admin->email_verified_at)->not->toBeNull();
});

it('menolak perintah create-admin untuk email yang sudah dipakai', function () {
    User::factory()->create(['email' => 'panitia@contoh.id']);

    $this->artisan('app:create-admin', [
        '--name' => 'Admin',
        '--email' => 'panitia@contoh.id',
        '--password' => 'rahasia-kuat-123',
    ])->assertFailed();
});
