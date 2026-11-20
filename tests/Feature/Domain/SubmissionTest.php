<?php

use App\Enums\GudepStatus;
use App\Events\GudepStatusChanged;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Gudep;
use App\Models\Member;
use Illuminate\Support\Facades\Event;

/** Gudep yang memenuhi semua syarat kirim: kuota, foto, data, dan dokumen. */
function readyGudep(GudepStatus $status = GudepStatus::Draft): Gudep
{
    $gudep = Gudep::factory()->status($status)->complete()->create();
    Member::query()->where('gudep_id', $gudep->id)->update(['photo_path' => 'photos/x.jpg']);

    foreach (DocumentType::query()->get() as $type) {
        Document::factory()->for($gudep)->create(['document_type_id' => $type->id]);
    }

    return $gudep->refresh();
}

it('menampilkan daftar periksa dengan kekurangan', function () {
    $gudep = Gudep::factory()->withOwner()->create();

    $this->actingAs($gudep->user)
        ->get(route('pembina.submission.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('pembina/submit')
            ->where('canSubmit', false)
            ->where('items.1.label', 'Regu Putra 0/8')
            ->where('items.1.ok', false)
            ->where('items.1.detail', 'Tambah 8 peserta lagi'));
});

it('daftar periksa menampilkan tiap dokumen gabungan', function () {
    $gudep = Gudep::factory()->withOwner()->create();

    $this->actingAs($gudep->user)
        ->get(route('pembina.submission.show'))
        ->assertInertia(function ($page) {
            $items = collect($page->toArray()['props']['items'])->keyBy('key');

            expect($items->keys()->all())
                ->toContain('gudep_kta', 'gudep_izin_ortu', 'gudep_kesehatan', 'gudep_kesiapan_sekolah')
                ->not->toContain('dokumen')
                ->and($items['gudep_kta'])->toMatchArray(['ok' => false, 'action' => 'Unggah', 'detail' => 'Belum diunggah']);
        });
});

it('menolak kirim saat belum lengkap dan status tetap draft', function () {
    $gudep = Gudep::factory()->complete()->create();

    $this->actingAs($gudep->user)
        ->post(route('pembina.submission.store'), ['agreement' => '1'])
        ->assertSessionHasErrors('submit');

    expect($gudep->refresh()->status)->toBe(GudepStatus::Draft);
});

it('mewajibkan pernyataan persetujuan', function () {
    $gudep = readyGudep();

    $this->actingAs($gudep->user)
        ->post(route('pembina.submission.store'), [])
        ->assertSessionHasErrors('agreement');
});

it('mengirim pendaftaran lengkap, mengunci data, dan memicu event', function () {
    Event::fake([GudepStatusChanged::class]);
    $gudep = readyGudep();

    $this->actingAs($gudep->user)
        ->post(route('pembina.submission.store'), ['agreement' => '1'])
        ->assertRedirect(route('pembina.submission.show'));

    $gudep->refresh();

    expect($gudep->status)->toBe(GudepStatus::Submitted)
        ->and($gudep->submitted_at)->not->toBeNull()
        ->and($gudep->user->can('update', $gudep))->toBeFalse();

    Event::assertDispatched(GudepStatusChanged::class, fn ($event) => $event->from === GudepStatus::Draft);

    $this->actingAs($gudep->user)
        ->get(route('pembina.submission.show'))
        ->assertInertia(fn ($page) => $page->component('pembina/submitted')->where('stats.peserta', 16));
});

it('mengizinkan kirim ulang dari status revisi', function () {
    $gudep = readyGudep(GudepStatus::Revision);
    $gudep->forceFill(['revision_deadline' => now()->addDay()])->save();

    $this->actingAs($gudep->user)
        ->post(route('pembina.submission.store'), ['agreement' => '1'])
        ->assertSessionHasNoErrors();

    expect($gudep->refresh())
        ->status->toBe(GudepStatus::Submitted)
        ->revision_deadline->toBeNull();
});

it('menolak kirim di luar jadwal pendaftaran', function () {
    $gudep = readyGudep();
    setRegistrationWindow(now()->subDays(30), now()->subMinute());

    $this->actingAs($gudep->user)
        ->post(route('pembina.submission.store'), ['agreement' => '1'])
        ->assertSessionHasErrors('registration');

    expect($gudep->refresh()->status)->toBe(GudepStatus::Draft);
});
