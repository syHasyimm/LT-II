<?php

use App\Enums\MemberRole;
use App\Enums\TemplateTarget;
use App\Enums\TemplateType;
use App\Models\Template;
use App\Services\Templates\DefaultTemplates;
use Illuminate\Support\Facades\Storage;

it('memasang template bawaan ID Card peserta, pembina, dan sertifikat', function () {
    Storage::fake('local');

    DefaultTemplates::install();

    expect(Template::query()->count())->toBe(3)
        ->and(Template::activeFor(TemplateType::IdCard, MemberRole::Peserta)->name)->toBe('ID Card Peserta 2026')
        ->and(Template::activeFor(TemplateType::IdCard, MemberRole::Pembina)->name)->toBe('ID Card Pembina 2026')
        ->and(Template::activeFor(TemplateType::Certificate, MemberRole::Pembina)->applies_to)->toBe(TemplateTarget::All);

    $idCard = Template::query()->where('type', TemplateType::IdCard)->first();
    expect($idCard->width_mm)->toBe(55.0)->and($idCard->height_mm)->toBe(85.0);
    Storage::disk('local')->assertExists($idCard->background_path);
});

it('mendahulukan template dengan sasaran spesifik daripada sasaran semua', function () {
    Template::factory()->create(['applies_to' => TemplateTarget::All, 'name' => 'Umum']);
    Template::factory()->create(['applies_to' => TemplateTarget::Peserta, 'name' => 'Khusus peserta']);

    expect(Template::activeFor(TemplateType::IdCard, MemberRole::Peserta)->name)->toBe('Khusus peserta')
        ->and(Template::activeFor(TemplateType::IdCard, MemberRole::Pembina)->name)->toBe('Umum');
});

it('hanya mengizinkan satu template aktif per jenis dan sasaran', function () {
    $old = Template::factory()->create(['applies_to' => TemplateTarget::Peserta]);
    $other = Template::factory()->create(['applies_to' => TemplateTarget::Pembina]);
    $new = Template::factory()->create(['applies_to' => TemplateTarget::Peserta, 'is_active' => false]);

    $new->activate();

    expect($new->refresh()->is_active)->toBeTrue()
        ->and($old->refresh()->is_active)->toBeFalse()
        ->and($other->refresh()->is_active)->toBeTrue();
});
