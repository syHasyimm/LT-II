<?php

use App\Enums\GudepStatus;
use App\Models\Gudep;

it('menampilkan data anggota sebagai read-only saat terkunci', function (GudepStatus $status) {
    $gudep = Gudep::factory()->status($status)->complete()->create();
    $member = $gudep->peserta()->first();

    $this->actingAs($gudep->user)
        ->get(route('pembina.members.edit', $member))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('editable', false));

    $this->actingAs($gudep->user)
        ->get(route('pembina.members.index'))
        ->assertInertia(fn ($page) => $page->where('editable', false));

    $this->actingAs($gudep->user)
        ->get(route('pembina.gudep.edit'))
        ->assertInertia(fn ($page) => $page->where('editable', false));
})->with([GudepStatus::Submitted, GudepStatus::Verified]);

it('menolak semua perubahan anggota saat terkunci', function (GudepStatus $status) {
    $gudep = Gudep::factory()->status($status)->complete()->create();
    $member = $gudep->peserta()->first();

    $this->actingAs($gudep->user)
        ->put(route('pembina.members.update', $member), ['name' => 'Ubah'])
        ->assertForbidden();

    $this->actingAs($gudep->user)
        ->delete(route('pembina.members.destroy', $member))
        ->assertForbidden();
})->with([GudepStatus::Submitted, GudepStatus::Verified]);

it('membuka kembali perubahan saat status revisi', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Revision)->complete()->create();

    $this->actingAs($gudep->user)
        ->get(route('pembina.members.index'))
        ->assertInertia(fn ($page) => $page->where('editable', true));
});
