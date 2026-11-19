<?php

use App\Enums\GudepStatus;
use App\Enums\MemberRole;
use App\Enums\TeamGroup;
use App\Models\Gudep;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLSXGen;

function importOwner(GudepStatus $status = GudepStatus::Draft): array
{
    $user = User::factory()->pembina()->create();
    $gudep = Gudep::factory()->for($user)->withOwner()->status($status)->create();

    return [$user, $gudep];
}

/** @param list<list<string>> $rows */
function xlsxUpload(array $rows, string $name = 'peserta.xlsx'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'imp');
    SimpleXLSXGen::fromArray($rows)->saveAs($path);

    return new UploadedFile($path, $name, null, null, true);
}

/** @return array{added: int, skipped: list<array{row: int, name: string, reason: string}>} */
function importReport(): array
{
    return session('inertia.flash_data.import');
}

function csvUpload(string $content, string $name = 'peserta.csv'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'imp');
    file_put_contents($path, $content);

    return new UploadedFile($path, $name, null, null, true);
}

it('menyediakan template impor Excel', function () {
    [$user] = importOwner();

    $response = $this->actingAs($user)->get(route('pembina.members.import.template'));

    $response->assertOk();
    $xlsx = SimpleXLSX::parseData($response->streamedContent());
    expect($xlsx->sheetNames())->toBe(['Peserta', 'Petunjuk'])
        ->and($xlsx->rows(0)[0])->toBe(['Regu', 'Nama lengkap', 'Tanggal lahir', 'Nomor KTA']);
});

it('mengimpor peserta dari Excel dan melaporkan baris yang tidak valid', function () {
    [$user, $gudep] = importOwner();

    $file = xlsxUpload([
        ['Regu', 'Nama lengkap', 'Tanggal lahir', 'Nomor KTA'],
        ['Putra', 'Ahmad   Fauzan', '15/03/2013', '12.04.041.0101'],
        ['pi', 'Nadia Putri', '2012-11-02', '12.04.041.0102'],
        ['Campuran', 'Salah Regu', '01/01/2013', '1'],
        ['Putri', 'Tanggal Salah', SimpleXLSXGen::raw('31/02/2013'), '2'],
        ['Putra', '', '01/01/2013', '3'],
        ['', '', '', ''],
        ['Putra', 'ahmad fauzan', '15-03-2013', '4'],
    ]);

    $this->actingAs($user)
        ->post(route('pembina.members.import.store'), ['file' => $file])
        ->assertRedirect(route('pembina.members.index'))
        ->assertInertiaFlash('import.added', 2);

    expect(collect(importReport()['skipped'])->pluck('row')->all())->toBe([4, 5, 6, 8]);

    $ahmad = $gudep->peserta(TeamGroup::Pa)->sole();
    expect($ahmad->name)->toBe('Ahmad Fauzan')
        ->and($ahmad->birth_date->format('Y-m-d'))->toBe('2013-03-15')
        ->and($ahmad->kta_number)->toBe('12.04.041.0101')
        ->and($ahmad->gender->value)->toBe('L')
        ->and($gudep->peserta(TeamGroup::Pi)->sole()->gender->value)->toBe('P');
});

it('mengimpor CSV bertitik koma dari Excel berbahasa Indonesia dengan urutan kolom bebas', function () {
    [$user, $gudep] = importOwner();

    $csv = "\xEF\xBB\xBFNo;Nomor KTA;Nama Lengkap;Tanggal Lahir;Regu\n1;12.04.1;Rudi Hartono;5/7/2013;Pa\n2;12.04.2;Sinta Dewi;20.08.2012;Putri\n";

    $this->actingAs($user)
        ->post(route('pembina.members.import.store'), ['file' => csvUpload($csv)])
        ->assertInertiaFlash('import.added', 2);

    expect($gudep->peserta(TeamGroup::Pa)->sole()->birth_date->format('Y-m-d'))->toBe('2013-07-05')
        ->and($gudep->peserta(TeamGroup::Pi)->sole()->name)->toBe('Sinta Dewi');
});

it('menjaga kuota 8 per regu termasuk peserta yang sudah ada', function () {
    [$user, $gudep] = importOwner();
    Member::factory()->for($gudep)->count(7)->create(['role' => MemberRole::Peserta, 'group' => TeamGroup::Pa]);

    $rows = [['Regu', 'Nama', 'Tanggal lahir', 'KTA']];
    foreach (range(1, 3) as $i) {
        $rows[] = ['Putra', "Peserta Baru {$i}", "0{$i}/01/2013", "9.{$i}"];
    }

    $this->actingAs($user)
        ->post(route('pembina.members.import.store'), ['file' => xlsxUpload($rows)])
        ->assertInertiaFlash('import.added', 1);

    expect(importReport()['skipped'])->toHaveCount(2)
        ->and(importReport()['skipped'][0]['reason'])->toContain('penuh');

    expect($gudep->peserta(TeamGroup::Pa)->count())->toBe(8);
});

it('menolak file tanpa judul kolom yang dikenali atau berformat lain', function () {
    [$user, $gudep] = importOwner();

    $this->actingAs($user)
        ->post(route('pembina.members.import.store'), ['file' => xlsxUpload([['A', 'B'], ['1', '2']])])
        ->assertSessionHasErrors('file');

    $this->post(route('pembina.members.import.store'), ['file' => UploadedFile::fake()->create('peserta.pdf', 10, 'application/pdf')])
        ->assertSessionHasErrors('file');

    $this->post(route('pembina.members.import.store'), ['file' => csvUpload('bukan xlsx', 'palsu.xlsx')])
        ->assertSessionHasErrors('file');

    expect($gudep->peserta()->count())->toBe(0);
});

it('tidak bisa mengimpor saat data terkunci', function () {
    [$user, $gudep] = importOwner(GudepStatus::Submitted);

    $this->actingAs($user)
        ->post(route('pembina.members.import.store'), ['file' => xlsxUpload([['Regu', 'Nama', 'Tanggal lahir', 'KTA'], ['Pa', 'A', '01/01/2013', '1']])])
        ->assertForbidden();

    expect($gudep->peserta()->count())->toBe(0);
});
