<?php

use App\Enums\DocumentStatus;
use App\Enums\Gender;
use App\Enums\GudepStatus;
use App\Enums\MemberRole;
use App\Enums\TeamGroup;
use App\Models\Document;
use App\Models\Gudep;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

function pesertaPayload(string $regu = 'pa', array $overrides = []): array
{
    return [
        'regu' => $regu,
        'name' => 'Raka Aditya',
        'birth_date' => '2013-03-12',
        'kta_number' => '12.04.041.0027',
        ...$overrides,
    ];
}

function pembinaPayload(array $overrides = []): array
{
    return [
        'regu' => 'pembina',
        'name' => 'Sri Wahyuni',
        'gender' => 'P',
        'birth_date' => '1988-05-01',
        'kta_number' => '12.04.041.0002',
        'phone' => '0813-2200-1145',
        'accompanies' => 'pi',
        ...$overrides,
    ];
}

it('mengarahkan pembina tanpa gudep ke form gudep', function () {
    $this->actingAs(User::factory()->pembina()->create())
        ->get(route('pembina.members.index'))
        ->assertRedirect(route('pembina.gudep.edit'));
});

it('menampilkan anggota per regu', function () {
    $gudep = Gudep::factory()->complete()->create();

    $this->actingAs($gudep->user)
        ->get(route('pembina.members.index', ['tab' => 'pi']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('pembina/members/index')
            ->where('tab', 'pi')
            ->has('members.pa', 8)
            ->has('members.pi', 8)
            ->has('members.pembina', 1));
});

it('menambah peserta dengan jenis kelamin mengikuti regu', function () {
    $gudep = Gudep::factory()->withOwner()->create();

    $this->actingAs($gudep->user)
        ->post(route('pembina.members.store'), pesertaPayload('pi', ['gender' => 'L']))
        ->assertRedirect(route('pembina.members.index', ['tab' => 'pi']));

    $member = $gudep->peserta(TeamGroup::Pi)->sole();

    expect($member->gender)->toBe(Gender::P)
        ->and($member->role)->toBe(MemberRole::Peserta);
});

it('menerima tanggal lahir dengan format hari/bulan/tahun', function () {
    $gudep = Gudep::factory()->withOwner()->create();

    $this->actingAs($gudep->user)
        ->post(route('pembina.members.store'), pesertaPayload('pa', ['birth_date' => '12/03/2013']))
        ->assertSessionHasNoErrors();

    expect($gudep->peserta()->sole()->birth_date->format('Y-m-d'))->toBe('2013-03-12');
});

it('menolak peserta ke-9 dalam satu regu', function () {
    $gudep = Gudep::factory()->complete()->create();

    $this->actingAs($gudep->user)
        ->post(route('pembina.members.store'), pesertaPayload('pa'))
        ->assertSessionHasErrors('regu');

    expect($gudep->peserta(TeamGroup::Pa)->count())->toBe(8);
});

it('menyimpan pas foto di disk privat dengan nama acak', function () {
    $gudep = Gudep::factory()->withOwner()->create();

    $this->actingAs($gudep->user)->post(route('pembina.members.store'), pesertaPayload('pa', [
        'photo' => UploadedFile::fake()->image('foto-raka.jpg', 300, 400),
    ]));

    $member = $gudep->peserta()->sole();

    expect($member->photo_path)->toStartWith("photos/{$gudep->id}/")
        ->not->toContain('foto-raka');
    Storage::disk('local')->assertExists($member->photo_path);
});

it('menolak pas foto yang bukan gambar atau melebihi 2 MB', function (UploadedFile $file) {
    $gudep = Gudep::factory()->withOwner()->create();

    $this->actingAs($gudep->user)
        ->post(route('pembina.members.store'), pesertaPayload('pa', ['photo' => $file]))
        ->assertSessionHasErrors('photo');
})->with([
    'pdf' => fn () => UploadedFile::fake()->create('foto.pdf', 100, 'application/pdf'),
    'terlalu besar' => fn () => UploadedFile::fake()->image('foto.jpg')->size(2049),
]);

it('menambah pembina pendamping tanpa batas jumlah', function () {
    $gudep = Gudep::factory()->withOwner()->create();

    foreach (range(1, 5) as $i) {
        $this->actingAs($gudep->user)
            ->post(route('pembina.members.store'), pembinaPayload(['name' => "Pembina {$i}"]))
            ->assertSessionHasNoErrors();
    }

    expect($gudep->pembina()->count())->toBe(6);
});

it('mewajibkan nomor HP dan keterangan mendampingi untuk pembina', function () {
    $gudep = Gudep::factory()->withOwner()->create();

    $this->actingAs($gudep->user)
        ->post(route('pembina.members.store'), pembinaPayload(['phone' => '', 'accompanies' => '']))
        ->assertSessionHasErrors(['phone', 'accompanies']);
});

it('menghapus pembina pendamping beserta fotonya', function () {
    $gudep = Gudep::factory()->withOwner()->create();
    Storage::disk('local')->put('photos/x.jpg', 'foto');
    $pembina = Member::factory()->for($gudep)->pembina()->create(['photo_path' => 'photos/x.jpg']);

    $this->actingAs($gudep->user)
        ->delete(route('pembina.members.destroy', $pembina))
        ->assertRedirect(route('pembina.members.index', ['tab' => 'pembina']));

    expect(Member::query()->find($pembina->id))->toBeNull();
    Storage::disk('local')->assertMissing('photos/x.jpg');
});

it('tidak mengizinkan pembina pemilik akun dihapus', function () {
    $gudep = Gudep::factory()->withOwner()->create();

    $this->actingAs($gudep->user)
        ->delete(route('pembina.members.destroy', $gudep->pembina()->sole()))
        ->assertForbidden();
});

it('menolak pembina mengubah anggota gudep lain', function () {
    $other = Gudep::factory()->complete()->create();
    $me = Gudep::factory()->withOwner()->create();
    $target = $other->peserta()->first();

    $this->actingAs($me->user)->get(route('pembina.members.edit', $target))->assertForbidden();
    $this->actingAs($me->user)->put(route('pembina.members.update', $target), pesertaPayload())->assertForbidden();
    $this->actingAs($me->user)->delete(route('pembina.members.destroy', $target))->assertForbidden();
});

it('menyajikan pas foto hanya untuk pemilik gudep dan admin', function () {
    Storage::disk('local')->put('photos/1/a.jpg', 'foto');
    $gudep = Gudep::factory()->withOwner()->create();
    $member = Member::factory()->for($gudep)->create(['photo_path' => 'photos/1/a.jpg']);

    $this->actingAs($gudep->user)->get(route('members.photo', $member))->assertOk();
    $this->actingAs(User::factory()->admin()->create())->get(route('members.photo', $member))->assertOk();
    $this->actingAs(User::factory()->pembina()->create())->get(route('members.photo', $member))->assertForbidden();
});

it('mengunci perubahan anggota setelah pendaftaran dikirim', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Submitted)->withOwner()->create();

    $this->actingAs($gudep->user)
        ->post(route('pembina.members.store'), pesertaPayload())
        ->assertForbidden();
});

it('membuka ulang berkas gabungan yang sudah disetujui saat anggota baru ditambahkan', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Revision)->withOwner()->create();
    $documents = collect(['kta', 'izin_ortu', 'kesehatan', 'kesiapan_sekolah'])
        ->mapWithKeys(fn (string $code) => [$code => Document::factory()->for($gudep)->type($code)->status(DocumentStatus::Approved)->create()]);
    $rejected = Document::factory()->for($gudep)->type('kta')->status(DocumentStatus::Rejected)->create(['archived_at' => now()]);

    $this->actingAs($gudep->user)
        ->post(route('pembina.members.store'), pesertaPayload('pa'))
        ->assertSessionHasNoErrors();

    foreach (['kta', 'izin_ortu', 'kesehatan'] as $code) {
        expect($documents[$code]->refresh())
            ->status->toBe(DocumentStatus::Pending)
            ->note->toContain('Raka Aditya');
    }

    expect($documents['kesiapan_sekolah']->refresh()->status)->toBe(DocumentStatus::Approved)
        ->and($rejected->refresh()->status)->toBe(DocumentStatus::Rejected);
});

it('tidak membuka ulang izin orang tua saat pembina pendamping ditambahkan', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Revision)->withOwner()->create();
    $izin = Document::factory()->for($gudep)->type('izin_ortu')->status(DocumentStatus::Approved)->create();
    $kta = Document::factory()->for($gudep)->type('kta')->status(DocumentStatus::Approved)->create();

    $this->actingAs($gudep->user)
        ->post(route('pembina.members.store'), pembinaPayload())
        ->assertSessionHasNoErrors();

    expect($izin->refresh()->status)->toBe(DocumentStatus::Approved)
        ->and($kta->refresh()->status)->toBe(DocumentStatus::Pending);
});
