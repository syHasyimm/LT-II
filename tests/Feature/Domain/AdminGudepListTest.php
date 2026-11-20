<?php

use App\Enums\GudepStatus;
use App\Models\Gudep;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('menampilkan ringkasan dashboard admin', function () {
    Gudep::factory()->status(GudepStatus::Submitted)->complete()->count(2)->create();
    Gudep::factory()->status(GudepStatus::Verified)->create();
    Gudep::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/dashboard')
            ->where('counts.all', 4)
            ->where('counts.submitted', 2)
            ->where('counts.verified', 1)
            ->where('summary.peserta', 32)
            ->where('adminCounters.submitted', 2));
});

it('memfilter daftar gudep berdasarkan status', function () {
    Gudep::factory()->status(GudepStatus::Submitted)->create(['name' => 'Gudep Dikirim']);
    Gudep::factory()->create(['name' => 'Gudep Draft']);

    $this->actingAs($this->admin)
        ->get(route('admin.gudeps.index', ['status' => 'submitted']))
        ->assertInertia(fn ($page) => $page
            ->component('admin/gudeps/index')
            ->has('rows.data', 1)
            ->where('rows.data.0.name', 'Gudep Dikirim')
            ->where('filters.status', 'submitted'));
});

it('mencari gudep berdasarkan nama atau sekolah', function () {
    Gudep::factory()->create(['name' => 'Gudep 07.041', 'institution' => 'SMP Negeri 3 Sleman']);
    Gudep::factory()->create(['name' => 'Gudep 05.112', 'institution' => 'SMA Negeri 1 Bantul']);

    $this->actingAs($this->admin)
        ->get(route('admin.gudeps.index', ['q' => 'Bantul']))
        ->assertInertia(fn ($page) => $page->has('rows.data', 1)->where('rows.data.0.name', 'Gudep 05.112'));
});

it('mencari gudep berdasarkan NPSN, penanggung jawab, atau alamat', function () {
    Gudep::factory()->create([
        'name' => 'Gudep 07.041',
        'npsn' => '20404123',
        'pic_name' => 'Sumarno',
        'address' => 'Jl. Magelang KM 5',
    ]);
    Gudep::factory()->create([
        'name' => 'Gudep 05.112',
        'npsn' => '20405112',
        'pic_name' => 'Siti Aminah',
        'address' => 'Jl. Imogiri Barat KM 5',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.gudeps.index', ['q' => '20404123']))
        ->assertInertia(fn ($page) => $page->has('rows.data', 1)->where('rows.data.0.name', 'Gudep 07.041'));

    $this->actingAs($this->admin)
        ->get(route('admin.gudeps.index', ['q' => 'Siti Aminah']))
        ->assertInertia(fn ($page) => $page->has('rows.data', 1)->where('rows.data.0.name', 'Gudep 05.112'));

    $this->actingAs($this->admin)
        ->get(route('admin.gudeps.index', ['q' => 'Imogiri']))
        ->assertInertia(fn ($page) => $page->has('rows.data', 1)->where('rows.data.0.name', 'Gudep 05.112'));
});

it('menampilkan gudep berstatus dikirim paling atas dan memakai paginasi', function () {
    Gudep::factory()->count(16)->create();
    Gudep::factory()->status(GudepStatus::Submitted)->create(['name' => 'Perlu Diperiksa']);

    $this->actingAs($this->admin)
        ->get(route('admin.gudeps.index'))
        ->assertInertia(fn ($page) => $page
            ->where('rows.data.0.name', 'Perlu Diperiksa')
            ->has('rows.data', 15)
            ->where('rows.total', 17));
});
