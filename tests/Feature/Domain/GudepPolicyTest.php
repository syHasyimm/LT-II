<?php

use App\Enums\GudepStatus;
use App\Models\Gudep;
use App\Models\Member;
use App\Models\User;

it('memberi tiap pembina tepat satu gudep', function () {
    $gudep = Gudep::factory()->withOwner()->create();

    expect($gudep->user->gudep->is($gudep))->toBeTrue()
        ->and($gudep->pembina()->sole()->is_owner)->toBeTrue();
});

it('menolak pembina mengakses gudep pembina lain', function () {
    $mine = Gudep::factory()->create();
    $theirs = Gudep::factory()->complete()->create();
    $member = $theirs->members()->first();

    expect($mine->user->can('view', $theirs))->toBeFalse()
        ->and($mine->user->can('update', $theirs))->toBeFalse()
        ->and($mine->user->can('update', $member))->toBeFalse()
        ->and($theirs->user->can('update', $member))->toBeTrue();
});

it('mengizinkan admin melihat semua gudep tetapi tidak mengubahnya', function () {
    $admin = User::factory()->admin()->create();
    $gudep = Gudep::factory()->create();

    expect($admin->can('view', $gudep))->toBeTrue()
        ->and($admin->can('update', $gudep))->toBeFalse();
});

it('mengunci perubahan saat pendaftaran sudah dikirim atau terverifikasi', function (GudepStatus $status, bool $editable) {
    $gudep = Gudep::factory()->status($status)->create();
    $member = Member::factory()->for($gudep)->create();

    expect($gudep->user->can('update', $gudep))->toBe($editable)
        ->and($gudep->user->can('update', $member))->toBe($editable);
})->with([
    [GudepStatus::Draft, true],
    [GudepStatus::Revision, true],
    [GudepStatus::Submitted, false],
    [GudepStatus::Verified, false],
]);

it('menampilkan statistik gudep di beranda publik', function () {
    Gudep::factory()->complete()->create();

    $this->get('/')->assertInertia(fn ($page) => $page
        ->where('stats.gudep', 1)
        ->where('stats.peserta', 16)
        ->where('stats.pembina', 1));
});

it('melarang menghapus pembina pemilik akun', function () {
    $gudep = Gudep::factory()->withOwner()->create();
    $owner = $gudep->pembina()->sole();
    $other = Member::factory()->for($gudep)->pembina()->create();

    expect($gudep->user->can('delete', $owner))->toBeFalse()
        ->and($gudep->user->can('delete', $other))->toBeTrue();
});
