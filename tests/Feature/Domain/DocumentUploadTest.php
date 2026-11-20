<?php

use App\Enums\DocumentStatus;
use App\Enums\GudepStatus;
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
        ->and(DocumentType::query()->pluck('scope')->unique()->values()->all())->toBe(['gudep'])
        ->and(docType('kta')->label)->toBe('KTA seluruh anggota (digabung 1 PDF)')
        ->and(docType('izin_ortu')->label)->toBe('Surat Izin Orang Tua seluruh peserta (digabung 1 PDF)')
        ->and(docType('kesehatan')->label)->toBe('Surat Keterangan Sehat seluruh anggota (digabung 1 PDF)');
});

it('menampilkan empat dokumen gudep dan daftar anggota', function () {
    $gudep = Gudep::factory()->complete()->create();

    $this->actingAs($gudep->user)
        ->get(route('pembina.documents.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('pembina/documents/index')
            ->has('rows', 17)
            ->has('columns', 0)
            ->has('gudepDocuments', 4)
            ->where('gudepDocuments.0.type.code', 'kta')
            ->where('counts.total', 4)
            ->where('counts.missing', 4));
});

it('mengunggah KTA gabungan ke disk privat sebagai pending', function () {
    $gudep = Gudep::factory()->complete()->create();

    $this->actingAs($gudep->user)
        ->post(route('pembina.documents.store'), [
            'document_type_id' => docType('kta')->id,
            'file' => pdf(),
        ])
        ->assertSessionHasNoErrors();

    $document = Document::query()->sole();

    expect($document->status)->toBe(DocumentStatus::Pending)
        ->and($document->member_id)->toBeNull()
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

it('menolak format selain PDF/JPG/PNG dan file di atas 10 MB', function (UploadedFile $file) {
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
    'terlalu besar' => fn () => pdf('besar.pdf', 10241),
]);

it('menerima file 9 MB', function () {
    $gudep = Gudep::factory()->withOwner()->create();

    $this->actingAs($gudep->user)
        ->post(route('pembina.documents.store'), [
            'document_type_id' => docType('kta')->id,
            'file' => pdf('kta.pdf', 9000),
        ])
        ->assertSessionHasNoErrors();

    expect(Document::query()->count())->toBe(1);
});

it('menolak dokumen gabungan yang dikirim dengan member_id', function (string $code) {
    $gudep = Gudep::factory()->withOwner()->create();

    $this->actingAs($gudep->user)
        ->post(route('pembina.documents.store'), [
            'document_type_id' => docType($code)->id,
            'member_id' => $gudep->pembina()->sole()->id,
            'file' => pdf(),
        ])
        ->assertSessionHasErrors('member_id');

    expect(Document::query()->count())->toBe(0);
})->with(['kta', 'izin_ortu', 'kesehatan']);

it('menolak unggah dengan member_id anggota gudep lain', function () {
    $other = Gudep::factory()->complete()->create();
    $me = Gudep::factory()->withOwner()->create();

    $this->actingAs($me->user)
        ->post(route('pembina.documents.store'), [
            'document_type_id' => docType('kta')->id,
            'member_id' => $other->peserta()->first()->id,
            'file' => pdf(),
        ])
        ->assertNotFound();

    expect(Document::query()->count())->toBe(0);
});

it('mengarsipkan dokumen lama saat diganti dan mengembalikan status ke pending', function () {
    $gudep = Gudep::factory()->withOwner()->create();
    $old = Document::factory()->for($gudep)->type('kta')->status(DocumentStatus::Rejected)->create([
        'note' => 'Foto KTA buram',
    ]);

    $this->actingAs($gudep->user)->post(route('pembina.documents.store'), [
        'document_type_id' => docType('kta')->id,
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
    Document::factory()->for($gudep)->type('kta')->create();
    Document::factory()->for($gudep)->type('kesehatan')->status(DocumentStatus::Rejected)->create();

    expect(DocumentChecklist::for($gudep)->counts())->toMatchArray([
        'total' => 4,
        'uploaded' => 1,
        'rejected' => 1,
        'missing' => 2,
    ]);
});

it('migrasi menghapus dokumen per anggota beserta filenya kecuali file yang masih dirujuk', function () {
    $gudep = Gudep::factory()->withOwner()->create();
    $member = $gudep->pembina()->sole();
    Storage::disk('local')->put('documents/x/lama.pdf', '%PDF');
    Storage::disk('local')->put('documents/x/bersama.pdf', '%PDF');
    Document::factory()->for($gudep)->type('kta')->create(['member_id' => $member->id, 'path' => 'documents/x/lama.pdf']);
    Document::factory()->for($gudep)->type('kesehatan')->create(['member_id' => $member->id, 'path' => 'documents/x/bersama.pdf', 'archived_at' => now()]);
    $kept = Document::factory()->for($gudep)->type('kesiapan_sekolah')->create(['path' => 'documents/x/bersama.pdf']);

    (require database_path('migrations/2026_11_20_000001_merge_member_documents_into_gudep.php'))->up();

    expect(Document::query()->pluck('id')->all())->toBe([$kept->id]);
    Storage::disk('local')->assertMissing('documents/x/lama.pdf');
    Storage::disk('local')->assertExists('documents/x/bersama.pdf');
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
