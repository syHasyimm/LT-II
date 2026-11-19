<?php

use App\Models\Gudep;
use App\Models\User;
use App\Services\RegistrationProgress;

it('menampilkan langkah awal untuk pembina tanpa gudep', function () {
    $this->actingAs(User::factory()->pembina()->create())
        ->get(route('pembina.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('pembina/dashboard')
            ->where('gudep', null)
            ->where('percent', 0)
            ->where('steps.0.state', 'current')
            ->has('steps', 6));
});

it('menandai langkah peserta sebagai langkah berikutnya setelah data gudep diisi', function () {
    $gudep = Gudep::factory()->withOwner()->create();

    $this->actingAs($gudep->user)
        ->get(route('pembina.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('steps.0.state', 'done')
            ->where('steps.1.key', 'peserta_pa')
            ->where('steps.1.state', 'current')
            ->where('steps.1.value', '0/8')
            ->where('steps.2.key', 'peserta_pi')
            ->where('steps.2.value', '0/8'));
});

it('melaporkan kekurangan yang menghalangi pengiriman', function () {
    $gudep = Gudep::factory()->withOwner()->create();

    $issues = RegistrationProgress::for($gudep)->issues();

    expect($issues)->toContain('Regu Putra baru 0/8 peserta.')
        ->toContain('Regu Putri baru 0/8 peserta.');
});

it('tidak melaporkan kekurangan kuota saat kedua regu penuh', function () {
    $gudep = Gudep::factory()->complete()->create();

    expect(RegistrationProgress::for($gudep)->issues())
        ->not->toContain('Regu Putra baru 8/8 peserta.')
        ->each->not->toContain('/8 peserta');
});
