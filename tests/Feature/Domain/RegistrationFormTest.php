<?php

use App\Enums\GudepStatus;
use App\Enums\MemberRole;
use App\Enums\TeamGroup;
use App\Models\Gudep;
use App\Models\Member;
use App\Models\User;
use App\Services\Pdf\RegistrationForm;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('admin mengunduh formulir pendaftaran gudep terverifikasi', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Verified)->complete()->create();

    $response = $this->actingAs($this->admin)->get(route('admin.gudeps.form', $gudep));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toBe('application/pdf')
        ->and($response->headers->get('content-disposition'))->toContain('formulir-pendaftaran-');
});

it('formulir memuat kop, identitas gudep, dan semua anggota', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Verified)->complete()->create(['npsn' => '20404123']);

    $html = app(RegistrationForm::class)->html($gudep);

    expect($html)
        ->toContain('KWARTIR RANTING KEPENUHAN')
        ->toContain('kwartirranting03kepenuhan@gmail.com')
        ->toContain('Formulir Pendaftaran Perkemahan LT 2')
        ->toContain('20404123')
        ->toContain('data:image/png;base64,');

    foreach ($gudep->members as $member) {
        expect($html)->toContain(e($member->name));
    }
});

it('formulir aman untuk karakter khusus dan kolom kosong', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Verified)->withOwner()->create(['address' => 'Jl. Merdeka & <i>Raya</i>']);
    Member::factory()->for($gudep)->create(['role' => MemberRole::Peserta, 'group' => TeamGroup::Pi, 'name' => 'Siti & <b>Ayu</b>', 'birth_date' => null]);
    $gudep->pembina()->sole()->update(['phone' => null, 'accompanies' => null]);

    $form = app(RegistrationForm::class);
    $html = $form->html($gudep->refresh());

    expect($html)->toContain('Siti &amp; &lt;b&gt;Ayu&lt;/b&gt;')
        ->toContain('Jl. Merdeka &amp; &lt;i&gt;Raya&lt;/i&gt;')
        ->not->toContain('<b>Ayu</b>')
        ->not->toContain('<i>Raya</i>');
    expect($form->pdf($gudep)->output())->toStartWith('%PDF');
});

it('menolak formulir untuk gudep belum terverifikasi', function (GudepStatus $status) {
    $gudep = Gudep::factory()->status($status)->withOwner()->create();

    $this->actingAs($this->admin)->get(route('admin.gudeps.form', $gudep))->assertNotFound();
})->with([GudepStatus::Draft, GudepStatus::Submitted, GudepStatus::Revision]);

it('pembina tidak dapat mengunduh formulir', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Verified)->withOwner()->create();

    $this->actingAs($gudep->user)->get(route('admin.gudeps.form', $gudep))->assertForbidden();
});
