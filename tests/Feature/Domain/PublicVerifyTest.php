<?php

use App\Actions\Generate\GenerateMemberFile;
use App\Enums\GudepStatus;
use App\Enums\TemplateType;
use App\Models\Gudep;
use App\Services\Templates\DefaultTemplates;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    DefaultTemplates::install();
});

it('menampilkan sertifikat yang sah tanpa data sensitif', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Verified)->withOwner()->create(['name' => 'Gudep 07.041', 'institution' => 'SMPN 3 Sleman']);
    $member = $gudep->pembina()->sole();
    $file = app(GenerateMemberFile::class)->handle($member, TemplateType::Certificate);

    $this->get(route('verify.show', $file->serial_number))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/verify')
            ->where('serial', 'LT2-2026-CERT-0001')
            ->where('result.name', $member->name)
            ->where('result.gudep', 'Gudep 07.041 · SMPN 3 Sleman')
            ->where('result.role', 'Pembina Pendamping')
            ->where('result.document_label', 'Sertifikat Pembina')
            ->missing('result.birth_date')
            ->missing('result.phone')
            ->missing('result.email'));
});

it('menerima nomor seri huruf kecil', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Verified)->withOwner()->create();
    app(GenerateMemberFile::class)->handle($gudep->pembina()->sole(), TemplateType::IdCard);

    $this->get('/verify/lt2-2026-id-0001')->assertOk()->assertInertia(fn ($page) => $page->where('result.type', 'id_card'));
});

it('menampilkan tidak ditemukan untuk nomor seri yang tidak terdaftar', function () {
    $this->get(route('verify.show', 'LT2-2026-CERT-9999'))
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page->component('public/verify')->where('result', null));
});

it('tidak mengesahkan berkas milik gudep yang statusnya berubah dari terverifikasi', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Verified)->withOwner()->create();
    $file = app(GenerateMemberFile::class)->handle($gudep->pembina()->sole(), TemplateType::Certificate);
    $gudep->forceFill(['status' => GudepStatus::Revision])->save();

    $this->get(route('verify.show', $file->serial_number))->assertNotFound();
});

it('mengarahkan form cek nomor seri ke halaman verifikasi', function () {
    $this->get('/verify?serial=lt2-2026-cert-0001')->assertRedirect(route('verify.show', 'LT2-2026-CERT-0001'));
    $this->get('/verify?serial=')->assertRedirect();
});
