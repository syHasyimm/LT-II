<?php

use App\Actions\Generate\GenerateMemberFile;
use App\Enums\GudepStatus;
use App\Enums\TemplateType;
use App\Models\GeneratedFile;
use App\Models\Gudep;
use App\Models\User;
use App\Services\SerialNumberService;
use App\Services\Templates\DefaultTemplates;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Storage::fake('local');
    DefaultTemplates::install();
    $this->admin = User::factory()->admin()->create();
});

it('memberi nomor seri berurutan per jenis dan mempertahankannya', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Verified)->complete()->create();
    [$a, $b] = $gudep->members()->orderBy('id')->take(2)->get()->all();
    $serials = app(SerialNumberService::class);

    $first = $serials->assign($a, TemplateType::IdCard);
    $second = $serials->assign($b, TemplateType::IdCard);
    $certificate = $serials->assign($a, TemplateType::Certificate);
    $again = $serials->assign($a, TemplateType::IdCard);

    expect($first->serial_number)->toBe('LT2-2026-ID-0001')
        ->and($second->serial_number)->toBe('LT2-2026-ID-0002')
        ->and($certificate->serial_number)->toBe('LT2-2026-CERT-0001')
        ->and($again->is($first))->toBeTrue();
});

it('membuat PDF di disk privat dan mempertahankan nomor seri saat generate ulang', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Verified)->complete()->create();
    $member = $gudep->peserta()->first();
    $generate = app(GenerateMemberFile::class);

    $file = $generate->handle($member, TemplateType::IdCard, $this->admin);
    Storage::disk('local')->assertExists($file->path);
    expect(Storage::disk('local')->get($file->path))->toStartWith('%PDF');

    $regenerated = $generate->handle($member->refresh(), TemplateType::IdCard, $this->admin);

    expect($regenerated->serial_number)->toBe($file->serial_number)
        ->and(GeneratedFile::query()->count())->toBe(1)
        ->and($regenerated->template_id)->not->toBeNull();
});

it('menolak generate untuk gudep yang belum terverifikasi', function () {
    $member = Gudep::factory()->status(GudepStatus::Submitted)->complete()->create()->peserta()->first();

    expect(fn () => app(GenerateMemberFile::class)->handle($member, TemplateType::IdCard))
        ->toThrow(ValidationException::class);
});

it('menjalankan generate satu gudep lewat queue dari halaman admin', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Verified)->withOwner()->create();
    Gudep::factory()->status(GudepStatus::Submitted)->complete()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.generate.store', 'sertifikat'), ['scope' => 'gudep', 'gudep_id' => $gudep->id])
        ->assertRedirect();

    expect(GeneratedFile::query()->where('type', TemplateType::Certificate)->count())->toBe(1);

    $this->actingAs($this->admin)
        ->get(route('admin.certificates.index'))
        ->assertInertia(fn ($page) => $page->component('admin/generate')->where('stats.generated', 1)->where('gudeps.0.generated', 1));
});

it('tidak memproses anggota gudep yang belum terverifikasi saat memilih semua', function () {
    Gudep::factory()->status(GudepStatus::Submitted)->complete()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.generate.store', 'id-card'), ['scope' => 'all'])
        ->assertSessionHasErrors('scope');

    expect(GeneratedFile::query()->count())->toBe(0);
});

it('mengunduh PDF satuan, PDF cetak gudep, lembar A4, dan ZIP', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Verified)->withOwner()->create();
    $member = $gudep->pembina()->sole();
    $file = app(GenerateMemberFile::class)->handle($member, TemplateType::IdCard, $this->admin);

    $this->actingAs($this->admin)->get(route('admin.generated.download', $file))->assertOk();

    $single = $this->actingAs($this->admin)->get(route('admin.generate.print', ['jenis' => 'id-card', 'gudep' => $gudep->id]));
    $single->assertOk();
    expect($single->headers->get('content-type'))->toBe('application/pdf');

    $this->actingAs($this->admin)
        ->get(route('admin.generate.print', ['jenis' => 'id-card', 'gudep' => $gudep->id, 'mode' => 'sheet']))
        ->assertOk();

    $zip = $this->actingAs($this->admin)->get(route('admin.generate.zip', ['jenis' => 'id-card', 'gudep' => $gudep->id]));
    $zip->assertOk();
    expect($zip->streamedContent())->toStartWith('PK');
});

it('hanya admin yang dapat mengunduh ID Card dan Sertifikat', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Verified)->withOwner()->create();
    $file = app(GenerateMemberFile::class)->handle($gudep->pembina()->sole(), TemplateType::IdCard, $this->admin);

    $this->actingAs($gudep->user)->get(route('admin.generated.download', $file))->assertForbidden();
    $this->actingAs($gudep->user)->get(route('admin.id-cards.index'))->assertForbidden();
});

it('menampilkan halaman generate untuk gudep yang belum punya berkas', function () {
    Gudep::factory()->status(GudepStatus::Verified)->withOwner()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.id-cards.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('gudeps.0.generated', 0)->where('gudeps.0.last_at', null));
});
