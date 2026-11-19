<?php

use App\Enums\GudepStatus;
use App\Models\Gudep;
use App\Models\User;

$payload = [
    'name' => 'Gudep 07.041',
    'gudep_number' => '07.041',
    'institution' => 'SMP Negeri 3 Sleman',
    'region' => 'Kwarcab Sleman',
];

it('menampilkan form gudep kosong untuk pembina baru', function () {
    $this->actingAs(User::factory()->pembina()->create())
        ->get(route('pembina.gudep.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('pembina/gudep/form')
            ->where('gudep', null)
            ->where('editable', true));
});

it('membuat gudep draft beserta pembina pemilik akun', function () use ($payload) {
    $user = User::factory()->pembina()->create(['name' => 'Rahmat Hidayat']);

    $this->actingAs($user)
        ->put(route('pembina.gudep.update'), $payload)
        ->assertRedirect(route('pembina.members.index'));

    $gudep = $user->refresh()->gudep;

    expect($gudep->status)->toBe(GudepStatus::Draft)
        ->and($gudep->gudep_number)->toBe('07.041')
        ->and($gudep->pembina()->sole())
        ->name->toBe('Rahmat Hidayat')
        ->is_owner->toBeTrue();
});

it('memperbarui gudep tanpa membuat pembina pemilik kedua', function () use ($payload) {
    $gudep = Gudep::factory()->withOwner()->create();

    $this->actingAs($gudep->user)
        ->put(route('pembina.gudep.update'), [...$payload, 'name' => 'Gudep Baru'])
        ->assertRedirect(route('pembina.dashboard'));

    expect($gudep->refresh()->name)->toBe('Gudep Baru')
        ->and($gudep->pembina()->count())->toBe(1)
        ->and(Gudep::query()->count())->toBe(1);
});

it('memvalidasi field wajib', function () {
    $this->actingAs(User::factory()->pembina()->create())
        ->put(route('pembina.gudep.update'), [])
        ->assertSessionHasErrors(['name', 'gudep_number', 'institution', 'region']);
});

it('menolak perubahan saat pendaftaran sudah dikirim', function () use ($payload) {
    $gudep = Gudep::factory()->status(GudepStatus::Submitted)->create();

    $this->actingAs($gudep->user)
        ->put(route('pembina.gudep.update'), $payload)
        ->assertForbidden();
});
