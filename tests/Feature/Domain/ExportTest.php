<?php

use App\Enums\DocumentStatus;
use App\Enums\GudepStatus;
use App\Enums\MemberRole;
use App\Enums\TeamGroup;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Gudep;
use App\Models\Member;
use App\Models\User;
use Shuchkin\SimpleXLSX;

function exportedWorkbook(string $content): SimpleXLSX
{
    $xlsx = SimpleXLSX::parseData($content);
    expect($xlsx)->toBeInstanceOf(SimpleXLSX::class);

    return $xlsx;
}

it('mengekspor gudep dan anggota ke Excel dengan status dokumen', function () {
    $admin = User::factory()->admin()->create();
    $gudep = Gudep::factory()->withOwner()->status(GudepStatus::Submitted)->create([
        'name' => 'Gudep 07.041',
        'npsn' => '20404123',
        'institution' => 'SMP Negeri 3 Sleman',
        'pic_name' => 'Drs. Sumarno, M.Pd.',
        'address' => 'Jl. Magelang KM 5, Sleman',
    ]);
    Member::factory()->for($gudep)->create([
        'role' => MemberRole::Peserta, 'group' => TeamGroup::Pa, 'name' => 'Budi Santoso', 'kta_number' => '=1+1',
    ]);
    Document::factory()->for($gudep)->type('kta')->status(DocumentStatus::Approved)->create();

    $response = $this->actingAs($admin)->get(route('admin.gudeps.excel'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('spreadsheetml')
        ->and($response->headers->get('content-disposition'))->toContain('rekap-pendaftaran-LT2-');

    $xlsx = exportedWorkbook($response->streamedContent());
    expect($xlsx->sheetNames())->toBe(['Gudep', 'Anggota']);

    $gudeps = $xlsx->rows(0);
    expect($gudeps[0][1])->toBe('Gudep')
        ->and($gudeps[1][1])->toBe('Gudep 07.041')
        ->and($gudeps[1][3])->toBe('20404123')
        ->and($gudeps[1][4])->toBe('SMP Negeri 3 Sleman')
        ->and($gudeps[1][5])->toBe('Drs. Sumarno, M.Pd.')
        ->and($gudeps[1][6])->toBe('Jl. Magelang KM 5, Sleman')
        ->and($gudeps[1][9])->toBe('Dikirim')
        ->and($gudeps[0])->toContain('KTA', 'Izin', 'Sehat', 'Kesiapan')
        ->and($gudeps[1][array_search('KTA', $gudeps[0], true)])->toBe(DocumentStatus::Approved->label())
        ->and($gudeps[1][array_search('Izin', $gudeps[0], true)])->toBe('Belum diunggah');

    $members = collect($xlsx->rows(1));
    $header = $members->first();
    $budi = $members->first(fn (array $row) => $row[1] === 'Budi Santoso');

    expect($header)->toContain('Nama', 'Peran', 'Gudep')
        ->not->toContain('Status dokumen')
        ->not->toContain('KTA')
        ->not->toContain('Izin')
        ->not->toContain('Sehat')
        ->and($budi[2])->toBe('Peserta')
        ->and($budi[3])->toBe('Putra')
        ->and($budi[8])->toBe('Gudep 07.041')
        // Isi pengguna tetap teks, tidak menjadi rumus.
        ->and($budi[6])->toBe('=1+1');

    expect(ActivityLog::query()->where('action', 'export.registrations')->exists())->toBeTrue();
});

it('mengikuti filter status dan pencarian di daftar gudep', function () {
    $admin = User::factory()->admin()->create();
    Gudep::factory()->withOwner()->status(GudepStatus::Verified)->create(['name' => 'Gudep Terverifikasi']);
    Gudep::factory()->withOwner()->status(GudepStatus::Draft)->create(['name' => 'Gudep Draf']);

    $response = $this->actingAs($admin)->get(route('admin.gudeps.excel', ['status' => 'verified']));

    $names = collect(exportedWorkbook($response->streamedContent())->rows(0))->skip(1)->pluck(1)->all();
    expect($names)->toBe(['Gudep Terverifikasi']);
});

it('hanya bisa diakses admin', function () {
    $pembina = User::factory()->pembina()->create();

    $this->actingAs($pembina)->get(route('admin.gudeps.excel'))->assertForbidden();
    auth()->logout();
    $this->get(route('admin.gudeps.excel'))->assertRedirect();
});
