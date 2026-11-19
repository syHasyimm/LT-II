<?php

use App\Enums\DocumentStatus;
use App\Enums\GudepStatus;
use App\Enums\TeamGroup;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Gudep;
use App\Models\Member;
use App\Models\User;
use App\Services\DocumentChecklist;
use App\Services\RegistrationProgress;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

function docType(string $code): DocumentType
{
    return DocumentType::query()->where('code', $code)->sole();
}

function pdf(string $name = 'kta.pdf', int $kb = 120): UploadedFile
{
    return UploadedFile::fake()->create($name, $kb, 'application/pdf');
}

it('memiliki jenis dokumen bawaan sesuai PRD', function () {
    expect(DocumentType::query()->orderBy('sort')->pluck('code')->all())
        ->toBe(['kta', 'izin_ortu', 'kesehatan', 'kesiapan_sekolah'])
        ->and(docType('izin_ortu')->required_for)->toBe(['peserta']);
});

it('menampilkan matriks kelengkapan dokumen', function () {
    $gudep = Gudep::factory()->complete()->create();

    $this->actingAs($gudep->user)
        ->get(route('pembina.documents.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('pembina/documents/index')
            ->has('rows', 17)
            ->has('columns', 3)
            ->has('gudepDocuments', 1)
            // 16 peserta × 3 dokumen + 1 pembina × 2 dokumen + 1 dokumen gudep
            ->where('counts.total', 51)
            ->where('counts.missing', 51));
});

it('mengunggah dokumen anggota ke disk privat sebagai pending', function () {
    $gudep = Gudep::factory()->complete()->create();
    $member = $gudep->peserta(TeamGroup::Pa)->first();

    $this->actingAs($gudep->user)
        ->post(route('pembina.documents.store'), [
            'document_type_id' => docType('kta')->id,
            'member_id' => $member->id,
            'file' => pdf(),
        ])
        ->assertSessionHasNoErrors();

    $document = Document::query()->sole();

    expect($document->status)->toBe(DocumentStatus::Pending)
        ->and($document->member_id)->toBe($member->id)
        ->and($document->original_name)->toBe('kta.pdf')
        ->and($document->path)->toStartWith("documents/{$gudep->id}/")
        ->not->toContain('kta.pdf');
    Storage::disk('local')->assertExists($document->path);
});

it('mengunggah dokumen gudep tanpa anggota', function () {
    $gudep = Gudep::factory()->withOwner()->create();

    $this->actingAs($gudep->user)
        ->post(route('pembina.documents.store'), [
            'document_type_id' => docType('kesiapan_sekolah')->id,
            'file' => pdf('kesiapan.pdf'),
        ])
        ->assertSessionHasNoErrors();

    expect(Document::query()->sole()->member_id)->toBeNull();
});

it('menolak format selain PDF/JPG/PNG dan file di atas 2 MB', function (UploadedFile $file) {
    $gudep = Gudep::factory()->withOwner()->create();

    $this->actingAs($gudep->user)
        ->post(route('pembina.documents.store'), [
            'document_type_id' => docType('kesiapan_sekolah')->id,
            'file' => $file,
        ])
        ->assertSessionHasErrors('file');

    expect(Document::query()->count())->toBe(0);
})->with([
    'docx' => fn () => UploadedFile::fake()->create('surat.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
    // File sungguhan (bukan fake) agar MIME dibaca dari isi file, bukan dari nama.
    'exe disamarkan' => function () {
        $path = tempnam(sys_get_temp_dir(), 'doc');
        file_put_contents($path, "MZ\x90\x00\x03\x00\x00\x00\x04\x00\x00\x00\xFF\xFF\x00\x00".str_repeat("\x00", 512).'This program cannot be run in DOS mode.');

        return new UploadedFile($path, 'kta.pdf', null, null, true);
    },
    'terlalu besar' => fn () => pdf('besar.pdf', 2049),
]);

it('menolak izin orang tua untuk pembina', function () {
    $gudep = Gudep::factory()->withOwner()->create();

    $this->actingAs($gudep->user)
        ->post(route('pembina.documents.store'), [
            'document_type_id' => docType('izin_ortu')->id,
            'member_id' => $gudep->pembina()->sole()->id,
            'file' => pdf(),
        ])
        ->assertSessionHasErrors('document_type_id');
});

it('menolak unggah untuk anggota gudep lain', function () {
    $other = Gudep::factory()->complete()->create();
    $me = Gudep::factory()->withOwner()->create();

    $this->actingAs($me->user)
        ->post(route('pembina.documents.store'), [
            'document_type_id' => docType('kta')->id,
            'member_id' => $other->peserta()->first()->id,
            'file' => pdf(),
        ])
        ->assertNotFound();
});

it('mengarsipkan dokumen lama saat diganti dan mengembalikan status ke pending', function () {
    $gudep = Gudep::factory()->withOwner()->create();
    $member = $gudep->pembina()->sole();
    $old = Document::factory()->for($gudep)->type('kta')->status(DocumentStatus::Rejected)->create([
        'member_id' => $member->id,
        'note' => 'Foto KTA buram',
    ]);

    $this->actingAs($gudep->user)->post(route('pembina.documents.store'), [
        'document_type_id' => docType('kta')->id,
        'member_id' => $member->id,
        'file' => pdf('kta-baru.pdf'),
    ]);

    expect($old->refresh()->archived_at)->not->toBeNull()
        ->and($gudep->documents()->active()->sole())
        ->original_name->toBe('kta-baru.pdf')
        ->status->toBe(DocumentStatus::Pending);
});

it('menyajikan dokumen hanya untuk pemilik gudep dan admin', function () {
    Storage::disk('local')->put('documents/1/a.pdf', '%PDF-1.4');
    $gudep = Gudep::factory()->withOwner()->create();
    $document = Document::factory()->for($gudep)->create(['path' => 'documents/1/a.pdf']);

    $this->actingAs($gudep->user)->get(route('documents.file', $document))->assertOk();
    $this->actingAs(User::factory()->admin()->create())->get(route('documents.file', $document))->assertOk();
    $this->actingAs(User::factory()->pembina()->create())->get(route('documents.file', $document))->assertForbidden();
    auth()->logout();
    $this->get(route('documents.file', $document))->assertRedirect();
});

it('mengunci unggahan setelah pendaftaran dikirim', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Submitted)->withOwner()->create();

    $this->actingAs($gudep->user)
        ->post(route('pembina.documents.store'), [
            'document_type_id' => docType('kesiapan_sekolah')->id,
            'file' => pdf(),
        ])
        ->assertForbidden();
});

it('menghitung dokumen diunggah, belum, dan ditolak', function () {
    $gudep = Gudep::factory()->withOwner()->create();
    $pembina = $gudep->pembina()->sole();
    Document::factory()->for($gudep)->type('kta')->create(['member_id' => $pembina->id]);
    Document::factory()->for($gudep)->type('kesehatan')->status(DocumentStatus::Rejected)->create(['member_id' => $pembina->id]);

    expect(DocumentChecklist::for($gudep)->counts())->toMatchArray([
        'total' => 3,
        'uploaded' => 1,
        'rejected' => 1,
        'missing' => 1,
    ]);
});

it('menyertakan kekurangan dokumen dalam kelengkapan pendaftaran', function () {
    $gudep = Gudep::factory()->complete()->create();
    Member::query()->update(['photo_path' => 'photos/x.jpg']);

    expect(RegistrationProgress::for($gudep->refresh())->issues())
        ->toContain('Gudep: Surat Kesiapan Sekolah belum diunggah.');
});

it('menyajikan dokumen secara inline agar bisa dipratinjau tanpa diunduh', function () {
    Storage::disk('local')->put('documents/1/b.pdf', '%PDF-1.4');
    $gudep = Gudep::factory()->withOwner()->create();
    $document = Document::factory()->for($gudep)->create([
        'path' => 'documents/1/b.pdf',
        'original_name' => 'kesiapan.pdf',
    ]);

    $response = $this->actingAs($gudep->user)->get(route('documents.file', $document));

    expect($response->headers->get('content-disposition'))->toStartWith('inline')
        ->and($response->headers->get('x-content-type-options'))->toBe('nosniff');
});
