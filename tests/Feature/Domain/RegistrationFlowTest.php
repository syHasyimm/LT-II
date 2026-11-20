<?php

use App\Enums\GudepStatus;
use App\Enums\TeamGroup;
use App\Enums\TemplateType;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\GeneratedFile;
use App\Models\User;
use App\Notifications\RegistrationStatusChanged;
use App\Services\Templates\DefaultTemplates;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

/**
 * Alur MVP end-to-end: pembina login Google → isi gudep, 16 peserta, pembina,
 * pas foto, dan dokumen → kirim → admin meninjau → minta revisi → pembina
 * memperbaiki → kirim ulang → admin menyetujui → terverifikasi → sertifikat
 * dibuat → QR membuka halaman verifikasi publik.
 */
it('menjalankan alur dari login pembina sampai sertifikat terverifikasi publik', function () {
    Storage::fake('local');
    Notification::fake();

    // 1. Pembina login dengan Google (akun dibuat otomatis).
    Socialite::shouldReceive('driver->user')->andReturn((new SocialiteUser)->map([
        'id' => 'google-777',
        'name' => 'Rahmat Hidayat',
        'email' => 'rahmat@example.com',
        'avatar' => null,
    ]));
    $this->get(route('auth.google.callback'))->assertRedirect(route('pembina.dashboard'));
    $pembina = User::query()->where('email', 'rahmat@example.com')->sole();

    // 2. Data gudep.
    $this->put(route('pembina.gudep.update'), [
        'gudep_number' => '07.041',
        'npsn' => '20404123',
        'institution' => 'SMP Negeri 3 Sleman',
        'pic_name' => 'Drs. Sumarno, M.Pd.',
        'address' => 'Jl. Magelang KM 5, Sleman',
    ])->assertSessionHasNoErrors();
    $gudep = $pembina->refresh()->gudep;

    // 3. 8 peserta putra + 8 peserta putri dengan pas foto.
    foreach (['pa', 'pi'] as $regu) {
        foreach (range(1, 8) as $i) {
            $this->post(route('pembina.members.store'), [
                'regu' => $regu,
                'name' => "Peserta {$regu} {$i}",
                'birth_date' => '2013-0'.(($i % 9) + 1).'-1'.$i,
                'kta_number' => "12.04.041.{$i}",
                'photo' => UploadedFile::fake()->image("foto-{$regu}-{$i}.jpg"),
            ])->assertSessionHasNoErrors();
        }
    }

    expect($gudep->peserta(TeamGroup::Pa)->count())->toBe(8)
        ->and($gudep->peserta(TeamGroup::Pi)->count())->toBe(8);

    // Peserta ke-9 ditolak.
    $this->post(route('pembina.members.store'), [
        'regu' => 'pa', 'name' => 'Peserta 9', 'birth_date' => '2013-01-01', 'kta_number' => '1',
    ])->assertSessionHasErrors('regu');

    // 4. Lengkapi data pembina pemilik akun + tambah satu pembina lain.
    $owner = $gudep->pembina()->sole();
    $this->put(route('pembina.members.update', $owner), [
        'name' => 'Rahmat Hidayat', 'gender' => 'L', 'birth_date' => '1985-02-02', 'kta_number' => '12.04.041.0001',
        'phone' => '0812-3456-7801', 'accompanies' => 'keduanya', 'photo' => UploadedFile::fake()->image('rahmat.jpg'),
    ])->assertSessionHasNoErrors();
    $this->post(route('pembina.members.store'), [
        'regu' => 'pembina', 'name' => 'Sri Wahyuni', 'gender' => 'P', 'birth_date' => '1988-05-01', 'kta_number' => '12.04.041.0002',
        'phone' => '0813-2200-1145', 'accompanies' => 'pi', 'photo' => UploadedFile::fake()->image('sri.jpg'),
    ])->assertSessionHasNoErrors();

    // Kirim sebelum dokumen lengkap ditolak.
    $this->post(route('pembina.submission.store'), ['agreement' => '1'])->assertSessionHasErrors('submit');

    // 5. Unggah semua dokumen wajib.
    foreach (DocumentType::query()->get() as $type) {
        $this->post(route('pembina.documents.store'), [
            'document_type_id' => $type->id,
            'file' => UploadedFile::fake()->create("{$type->code}.pdf", 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();
    }

    // 6. Kirim pendaftaran.
    $this->get(route('pembina.submission.show'))->assertInertia(fn ($page) => $page->where('canSubmit', true));
    $this->post(route('pembina.submission.store'), ['agreement' => '1'])->assertSessionHasNoErrors();
    expect($gudep->refresh()->status)->toBe(GudepStatus::Submitted);

    // 7. Admin menolak satu dokumen dan meminta revisi.
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    $rejected = $gudep->documents()->active()->first();
    $this->post(route('admin.documents.review', $rejected), ['decision' => 'rejected', 'note' => 'Buram'])->assertSessionHasNoErrors();
    $this->post(route('admin.gudeps.reject', $gudep), ['note' => 'Perbaiki dokumen yang ditolak.'])->assertSessionHasNoErrors();
    expect($gudep->refresh()->status)->toBe(GudepStatus::Revision);

    // 8. Pembina mengunggah ulang dan mengirim kembali.
    $this->actingAs($pembina);
    $this->post(route('pembina.documents.store'), [
        'document_type_id' => $rejected->document_type_id,
        'file' => UploadedFile::fake()->create('ulang.pdf', 100, 'application/pdf'),
    ])->assertSessionHasNoErrors();
    $this->post(route('pembina.submission.store'), ['agreement' => '1'])->assertSessionHasNoErrors();
    expect($gudep->refresh()->status)->toBe(GudepStatus::Submitted)
        ->and($rejected->refresh()->archived_at)->not->toBeNull();

    // 9. Admin menyetujui semua dokumen lalu memverifikasi gudep.
    $this->actingAs($admin);
    foreach ($gudep->documents()->active()->get() as $document) {
        if ($document->status->value !== 'approved') {
            $this->post(route('admin.documents.review', $document), ['decision' => 'approved'])->assertSessionHasNoErrors();
        }
    }
    $this->post(route('admin.gudeps.approve', $gudep))->assertSessionHasNoErrors();

    expect($gudep->refresh()->status)->toBe(GudepStatus::Verified)
        ->and(Document::query()->where('gudep_id', $gudep->id)->active()->count())->toBe(4);

    // Pembina menerima email untuk setiap perubahan status: kirim, revisi, kirim ulang, terverifikasi.
    Notification::assertSentToTimes($pembina, RegistrationStatusChanged::class, 4);

    // Data terkunci setelah terverifikasi.
    $this->actingAs($pembina);
    $this->get(route('pembina.submission.show'))->assertInertia(fn ($page) => $page->component('pembina/submitted')->where('status', 'verified'));

    // 10. Admin membuat sertifikat untuk seluruh anggota gudep (queue sync di tes).
    DefaultTemplates::install();
    $this->actingAs($admin)
        ->post(route('admin.generate.store', 'sertifikat'), ['scope' => 'gudep', 'gudep_id' => $gudep->id])
        ->assertRedirect();

    $certificates = GeneratedFile::query()->where('type', TemplateType::Certificate)->orderBy('sequence')->get();
    expect($certificates)->toHaveCount(18)
        ->and($certificates->first()->serial_number)->toBe('LT2-2026-CERT-0001');

    // 11. QR sertifikat membuka halaman verifikasi publik yang menyatakan sah.
    auth()->logout();
    $this->get(route('verify.show', $certificates->last()->serial_number))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('result.type', 'certificate'));
});
