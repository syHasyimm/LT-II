<?php

use App\Enums\GudepStatus;
use App\Models\Gudep;
use App\Models\User;

$payload = [
    'gudep_number' => '07.041',
    'npsn' => '20404123',
    'institution' => 'SMP Negeri 3 Sleman',
    'pic_name' => 'Drs. Sumarno, M.Pd.',
    'address' => 'Jl. Magelang KM 5, Sleman',
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
        ->and($gudep->name)->toBe('Gudep 07.041')
        ->and($gudep->gudep_number)->toBe('07.041')
        ->and($gudep->npsn)->toBe('20404123')
        ->and($gudep->pic_name)->toBe('Drs. Sumarno, M.Pd.')
        ->and($gudep->address)->toBe('Jl. Magelang KM 5, Sleman')
        ->and($gudep->pembina()->sole())
        ->name->toBe('Rahmat Hidayat')
        ->is_owner->toBeTrue();
});

it('memperbarui gudep tanpa membuat pembina pemilik kedua', function () use ($payload) {
    $gudep = Gudep::factory()->withOwner()->create();

    $this->actingAs($gudep->user)
        ->put(route('pembina.gudep.update'), [...$payload, 'gudep_number' => '07.042'])
        ->assertRedirect(route('pembina.dashboard'));

    expect($gudep->refresh()->name)->toBe('Gudep 07.042')
        ->and($gudep->pembina()->count())->toBe(1)
        ->and(Gudep::query()->count())->toBe(1);
});

it('memvalidasi field wajib', function () {
    $this->actingAs(User::factory()->pembina()->create())
        ->put(route('pembina.gudep.update'), [])
        ->assertSessionHasErrors(['gudep_number', 'npsn', 'institution', 'pic_name', 'address']);
});

it('menolak NPSN yang bukan 8 digit', function () use ($payload) {
    $this->actingAs(User::factory()->pembina()->create())
        ->put(route('pembina.gudep.update'), [...$payload, 'npsn' => '2040412'])
        ->assertSessionHasErrors('npsn');
});

it('menolak perubahan saat pendaftaran sudah dikirim', function () use ($payload) {
    $gudep = Gudep::factory()->status(GudepStatus::Submitted)->create();

    $this->actingAs($gudep->user)
        ->put(route('pembina.gudep.update'), $payload)
        ->assertForbidden();
});
