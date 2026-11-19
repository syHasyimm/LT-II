<?php

use App\Enums\MemberRole;
use App\Enums\TeamGroup;
use App\Models\Gudep;
use App\Models\Member;
use App\Models\User;

/** Alur pembina per langkah: timeline membaca pembinaSteps dan pembinaStep. */
it('menandai langkah sesuai halaman', function (string $route, array $params, string $expected) {
    $gudep = Gudep::factory()->withOwner()->create();

    $this->actingAs($gudep->user)
        ->get(route($route, $params))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('pembinaStep', $expected));
})->with([
    'beranda' => ['pembina.dashboard', [], 'ringkasan'],
    'data gudep' => ['pembina.gudep.edit', [], 'gudep'],
    'tab putri' => ['pembina.members.index', ['tab' => 'pi'], 'peserta_pi'],
    'tab pembina' => ['pembina.members.index', ['tab' => 'pembina'], 'pembina'],
    'tab tidak valid' => ['pembina.members.index', ['tab' => 'xyz'], 'peserta_pa'],
    'tambah putra' => ['pembina.members.create', ['regu' => 'pa'], 'peserta_pa'],
    'dokumen' => ['pembina.documents.index', [], 'berkas'],
    'kirim' => ['pembina.submission.show', [], 'kirim'],
]);

it('menandai langkah regu saat mengedit anggota', function () {
    $gudep = Gudep::factory()->withOwner()->create();
    $putri = Member::factory()->for($gudep)->create(['role' => MemberRole::Peserta, 'group' => TeamGroup::Pi]);
    $pembina = $gudep->pembina()->sole();

    $this->actingAs($gudep->user)
        ->get(route('pembina.members.edit', $putri))
        ->assertInertia(fn ($page) => $page->where('pembinaStep', 'peserta_pi'));

    $this->get(route('pembina.members.edit', $pembina))
        ->assertInertia(fn ($page) => $page->where('pembinaStep', 'pembina'));
});

it('membagikan enam langkah untuk pembina tanpa gudep', function () {
    $this->actingAs(User::factory()->pembina()->create())
        ->get(route('pembina.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('pembinaSteps', 6)
            ->where('pembinaSteps.0.key', 'gudep')
            ->where('pembinaSteps.0.state', 'current')
            ->where('pembinaSteps.1.state', 'todo')
            ->where('pembinaSteps.5.key', 'kirim'));
});

it('tidak membagikan langkah ke admin', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('pembinaSteps', null)
            ->where('pembinaStep', null));
});
