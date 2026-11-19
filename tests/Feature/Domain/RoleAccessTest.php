<?php

use App\Models\User;

it('memberi 403 kepada pembina yang membuka area admin', function (string $url) {
    $pembina = User::factory()->pembina()->create();

    $this->actingAs($pembina)->get($url)->assertForbidden();
})->with(['/admin', '/settings/profile']);

it('mengarahkan admin yang membuka area pembina ke dashboard admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('pembina.dashboard'))
        ->assertRedirect(route('admin.dashboard'));
});

it('mengizinkan pembina membuka area pembina', function () {
    $this->actingAs(User::factory()->pembina()->create())
        ->get(route('pembina.dashboard'))
        ->assertOk();
});

it('mengarahkan pengguna yang sudah masuk menjauh dari halaman masuk', function () {
    $this->actingAs(User::factory()->pembina()->create())
        ->get(route('pembina.login'))
        ->assertRedirect(route('pembina.dashboard'));

    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/login')
        ->assertRedirect(route('admin.dashboard'));
});
