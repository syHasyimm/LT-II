<?php

use App\Actions\Generate\QueueGudepIdCards;
use App\Enums\DocumentStatus;
use App\Enums\GudepStatus;
use App\Enums\MemberRole;
use App\Enums\TemplateTarget;
use App\Enums\TemplateType;
use App\Events\GudepStatusChanged;
use App\Jobs\GenerateFileJob;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Gudep;
use App\Models\Member;
use App\Models\Template;
use App\Models\User;
use App\Services\Templates\DefaultTemplates;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

/** Gudep berstatus dikirim dengan semua dokumen wajib berstatus $status. */
function submittedGudep(DocumentStatus $status = DocumentStatus::Pending): Gudep
{
    $gudep = Gudep::factory()->status(GudepStatus::Submitted)->complete()->create();
    Member::query()->where('gudep_id', $gudep->id)->update(['photo_path' => 'photos/x.jpg']);

    foreach (DocumentType::query()->get() as $type) {
        Document::factory()->for($gudep)->status($status)->create(['document_type_id' => $type->id]);
    }

    return $gudep->refresh();
}

it('menampilkan panel verifikasi gudep', function () {
    $gudep = submittedGudep();

    $this->actingAs($this->admin)
        ->get(route('admin.gudeps.show', $gudep))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/gudeps/show')
            ->where('summary.total', 4)
            ->where('summary.pending', 4)
            ->where('canReview', true)
            ->where('canVerify', false)
            ->has('rows', 17));
});

it('menyetujui dokumen dan mencatat peninjau', function () {
    $gudep = submittedGudep();
    $document = $gudep->documents()->first();

    $this->actingAs($this->admin)
        ->post(route('admin.documents.review', $document), ['decision' => 'approved'])
        ->assertRedirect();

    expect($document->refresh())
        ->status->toBe(DocumentStatus::Approved)
        ->reviewed_by->toBe($this->admin->id)
        ->reviewed_at->not->toBeNull();
});

it('mewajibkan catatan saat menolak dokumen', function () {
    $document = submittedGudep()->documents()->first();

    $this->actingAs($this->admin)
        ->post(route('admin.documents.review', $document), ['decision' => 'rejected', 'note' => ''])
        ->assertSessionHasErrors('note');

    $this->actingAs($this->admin)
        ->post(route('admin.documents.review', $document), ['decision' => 'rejected', 'note' => 'Buram'])
        ->assertSessionHasNoErrors();

    expect($document->refresh())->status->toBe(DocumentStatus::Rejected)->note->toBe('Buram');
});

it('tidak bisa memverifikasi gudep sebelum semua dokumen disetujui', function () {
    $gudep = submittedGudep();

    $this->actingAs($this->admin)
        ->post(route('admin.gudeps.approve', $gudep))
        ->assertSessionHasErrors('verify');

    expect($gudep->refresh()->status)->toBe(GudepStatus::Submitted);
});

it('memverifikasi gudep setelah semua dokumen disetujui', function () {
    Event::fake([GudepStatusChanged::class]);
    $gudep = submittedGudep(DocumentStatus::Approved);

    $this->actingAs($this->admin)
        ->post(route('admin.gudeps.approve', $gudep))
        ->assertRedirect(route('admin.gudeps.show', $gudep));

    expect($gudep->refresh())
        ->status->toBe(GudepStatus::Verified)
        ->verified_at->not->toBeNull();

    Event::assertDispatched(GudepStatusChanged::class, fn ($event) => $event->gudep->is($gudep) && $event->from === GudepStatus::Submitted);
});

it('mengembalikan gudep untuk revisi dengan catatan sehingga pembina bisa memperbaiki', function () {
    $gudep = submittedGudep();

    $this->actingAs($this->admin)
        ->post(route('admin.gudeps.reject', $gudep), ['note' => 'Surat kesehatan dua peserta buram.'])
        ->assertRedirect(route('admin.gudeps.show', $gudep));

    $gudep->refresh();

    expect($gudep->status)->toBe(GudepStatus::Revision)
        ->and($gudep->admin_note)->toBe('Surat kesehatan dua peserta buram.')
        ->and($gudep->user->can('update', $gudep))->toBeTrue();

    $this->actingAs($gudep->user)
        ->get(route('pembina.dashboard'))
        ->assertInertia(fn ($page) => $page->where('gudep.admin_note', 'Surat kesehatan dua peserta buram.'));
});

it('mewajibkan tenggat revisi bila pendaftaran sudah ditutup', function () {
    setRegistrationWindow(now()->subDays(30), now()->subDay());
    $gudep = submittedGudep();

    $this->actingAs($this->admin)
        ->post(route('admin.gudeps.reject', $gudep), ['note' => 'Perbaiki'])
        ->assertSessionHasErrors('revision_deadline');

    $deadline = now(config('camp.timezone'))->addDays(3)->format('Y-m-d\TH:i');

    $this->actingAs($this->admin)
        ->post(route('admin.gudeps.reject', $gudep), ['note' => 'Perbaiki', 'revision_deadline' => $deadline])
        ->assertSessionHasNoErrors();

    expect($gudep->refresh()->revision_deadline)->not->toBeNull()
        ->and($gudep->user->can('update', $gudep))->toBeTrue();
});

it('mengatur dan menghapus perpanjangan per gudep', function () {
    $gudep = Gudep::factory()->create();
    $until = now(config('camp.timezone'))->addDays(5)->format('Y-m-d\TH:i');

    $this->actingAs($this->admin)
        ->put(route('admin.gudeps.extend', $gudep), ['deadline_extended_until' => $until])
        ->assertSessionHasNoErrors();

    expect($gudep->refresh()->deadline_extended_until)->not->toBeNull();

    $this->actingAs($this->admin)
        ->put(route('admin.gudeps.extend', $gudep), ['deadline_extended_until' => ''])
        ->assertSessionHasNoErrors();

    expect($gudep->refresh()->deadline_extended_until)->toBeNull();
});

it('menolak pembina mengakses panel verifikasi', function () {
    $gudep = submittedGudep();

    $this->actingAs($gudep->user)->get(route('admin.gudeps.show', $gudep))->assertForbidden();
    $this->actingAs($gudep->user)->post(route('admin.documents.review', $gudep->documents()->first()), ['decision' => 'approved'])->assertForbidden();
});

it('hanya meninjau dokumen gudep berstatus dikirim', function () {
    $gudep = Gudep::factory()->withOwner()->create();
    $document = Document::factory()->for($gudep)->create();

    $this->actingAs($this->admin)
        ->post(route('admin.documents.review', $document), ['decision' => 'approved'])
        ->assertSessionHasErrors('document');
});

it('membuat ID Card otomatis untuk semua anggota saat gudep diverifikasi', function () {
    Bus::fake();
    DefaultTemplates::install();
    $gudep = submittedGudep(DocumentStatus::Approved);

    $this->actingAs($this->admin)->post(route('admin.gudeps.approve', $gudep))->assertSessionHasNoErrors();

    $count = $gudep->members()->count();
    Bus::assertBatched(fn (PendingBatch $batch) => $batch->jobs->count() === $count
        && $batch->jobs->every(fn ($job) => $job instanceof GenerateFileJob && $job->type === TemplateType::IdCard));
});

it('hanya mengantrikan anggota yang perannya punya template ID Card aktif', function () {
    Bus::fake();
    DefaultTemplates::install();
    Template::query()->where('type', TemplateType::IdCard)->where('applies_to', TemplateTarget::Pembina)->update(['is_active' => false]);
    $gudep = submittedGudep(DocumentStatus::Approved);

    $this->actingAs($this->admin)->post(route('admin.gudeps.approve', $gudep))->assertSessionHasNoErrors();

    $peserta = $gudep->members()->where('role', MemberRole::Peserta)->count();
    Bus::assertBatched(fn (PendingBatch $batch) => $batch->jobs->count() === $peserta);
});

it('tetap memverifikasi tanpa template ID Card', function () {
    Bus::fake();
    $gudep = submittedGudep(DocumentStatus::Approved);

    $this->actingAs($this->admin)->post(route('admin.gudeps.approve', $gudep))->assertSessionHasNoErrors();

    expect($gudep->refresh()->status)->toBe(GudepStatus::Verified);
    Bus::assertNothingBatched();
});

it('verifikasi tetap berhasil walau antrean ID Card gagal dikirim', function () {
    $gudep = submittedGudep(DocumentStatus::Approved);
    $this->mock(QueueGudepIdCards::class)->shouldReceive('handle')->andThrow(new RuntimeException('antrean mati'));

    $this->actingAs($this->admin)
        ->post(route('admin.gudeps.approve', $gudep))
        ->assertRedirect(route('admin.gudeps.show', $gudep))
        ->assertSessionHasNoErrors();

    expect($gudep->refresh()->status)->toBe(GudepStatus::Verified);
});
