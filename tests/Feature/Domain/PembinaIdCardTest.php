<?php

use App\Actions\Generate\GenerateMemberFile;
use App\Enums\GudepStatus;
use App\Enums\MemberRole;
use App\Enums\TeamGroup;
use App\Enums\TemplateType;
use App\Models\Gudep;
use App\Models\Member;
use App\Models\Template;
use App\Models\User;
use App\Services\Templates\DefaultTemplates;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    DefaultTemplates::install();
    $this->admin = User::factory()->admin()->create();
});

it('pembina mengunduh ID Card gudep terverifikasi dalam lembar A4', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Verified)->withOwner()->create();
    app(GenerateMemberFile::class)->handle($gudep->pembina()->sole(), TemplateType::IdCard, $this->admin);

    $response = $this->actingAs($gudep->user)->get(route('pembina.id-card'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toBe('application/pdf')
        ->and($response->headers->get('content-disposition'))->toContain('-a4.pdf');
});

it('menolak unduhan ID Card sebelum gudep terverifikasi', function (GudepStatus $status) {
    $gudep = Gudep::factory()->status($status)->withOwner()->create();

    $this->actingAs($gudep->user)->get(route('pembina.id-card'))->assertNotFound();
})->with([GudepStatus::Draft, GudepStatus::Submitted, GudepStatus::Revision]);

it('mengembalikan ke halaman terverifikasi saat ID Card belum siap', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Verified)->withOwner()->create();

    $this->actingAs($gudep->user)->get(route('pembina.id-card'))
        ->assertRedirect(route('pembina.submission.show'))
        ->assertInertiaFlash('toast.message', 'ID Card sedang disiapkan panitia. Coba lagi beberapa saat lagi.');
});

it('halaman terverifikasi memberi tahu kesiapan ID Card', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Verified)->withOwner()->create();

    $this->actingAs($gudep->user)
        ->get(route('pembina.submission.show'))
        ->assertInertia(fn ($page) => $page->component('pembina/submitted')->where('idCardReady', false));

    app(GenerateMemberFile::class)->handle($gudep->pembina()->sole(), TemplateType::IdCard, $this->admin);

    $this->actingAs($gudep->user)
        ->get(route('pembina.submission.show'))
        ->assertInertia(fn ($page) => $page->where('idCardReady', true));
});

it('admin dialihkan dari rute ID Card pembina', function () {
    $this->actingAs($this->admin)->get(route('pembina.id-card'))->assertRedirect(route('admin.dashboard'));
});

it('ID Card baru siap setelah kartu semua anggota selesai dibuat', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Verified)->withOwner()->create();
    $peserta = Member::factory()->for($gudep)->create(['role' => MemberRole::Peserta, 'group' => TeamGroup::Pa]);
    app(GenerateMemberFile::class)->handle($gudep->pembina()->sole(), TemplateType::IdCard, $this->admin);

    $this->actingAs($gudep->user)
        ->get(route('pembina.submission.show'))
        ->assertInertia(fn ($page) => $page->where('idCardReady', false));
    $this->actingAs($gudep->user)->get(route('pembina.id-card'))->assertRedirect(route('pembina.submission.show'));

    app(GenerateMemberFile::class)->handle($peserta, TemplateType::IdCard, $this->admin);

    $this->actingAs($gudep->user)
        ->get(route('pembina.submission.show'))
        ->assertInertia(fn ($page) => $page->where('idCardReady', true));
});

it('ID Card tidak siap bila template ID Card dinonaktifkan', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Verified)->withOwner()->create();
    app(GenerateMemberFile::class)->handle($gudep->pembina()->sole(), TemplateType::IdCard, $this->admin);
    Template::query()->where('type', TemplateType::IdCard)->update(['is_active' => false]);

    $this->actingAs($gudep->user)
        ->get(route('pembina.submission.show'))
        ->assertInertia(fn ($page) => $page->where('idCardReady', false));
});
