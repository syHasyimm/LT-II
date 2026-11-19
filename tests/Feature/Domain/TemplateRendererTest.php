<?php

use App\Models\Gudep;
use App\Models\Template;
use App\Services\Pdf\QrCodeService;
use App\Services\Pdf\RenderSubject;
use App\Services\Pdf\TemplateRenderer;
use App\Services\Templates\DefaultTemplates;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    DefaultTemplates::install();
});

it('mengisi placeholder dengan data anggota', function () {
    $gudep = Gudep::factory()->complete()->create(['name' => 'Gudep 07.041', 'institution' => 'SMPN 3 Sleman', 'region' => 'Kwarcab Sleman']);
    $member = $gudep->peserta()->first();

    $subject = RenderSubject::fromMember($member, 'LT2-2026-ID-0001');

    expect($subject->interpolate('{nama} · {peran} · {gudep} – {sekolah}, {kwartir} · {nomor_seri}'))
        ->toBe("{$member->name} · Peserta · Regu Putra · Gudep 07.041 – SMPN 3 Sleman, Kwarcab Sleman · LT2-2026-ID-0001")
        ->and($subject->qrContent)->toBe(route('verify.show', 'LT2-2026-ID-0001'));
});

it('membuat QR sebagai gambar PNG', function () {
    $uri = app(QrCodeService::class)->pngDataUri('https://contoh.id/verify/LT2-2026-CERT-0001');
    $image = imagecreatefromstring(base64_decode(substr($uri, strlen('data:image/png;base64,'))));

    expect($uri)->toStartWith('data:image/png;base64,')
        ->and(imagesx($image))->toBe(imagesy($image));
});

it('merender ID Card ke PDF seukuran template', function () {
    $template = Template::query()->where('name', 'ID Card Peserta 2026')->sole();

    $output = app(TemplateRenderer::class)->pdf($template, [RenderSubject::sample()])->output();

    expect($output)->toStartWith('%PDF')
        // 55 × 85 mm = 155,9 × 240,9 pt
        ->and($output)->toMatch('/MediaBox \[0(\.0+)? 0(\.0+)? 155\.9\d* 240\.9\d*\]/');
});

it('menyusun 9 kartu per lembar A4 dengan tanda potong', function () {
    $template = Template::query()->where('name', 'ID Card Peserta 2026')->sole();
    $subjects = array_fill(0, 10, RenderSubject::sample());

    $html = app(TemplateRenderer::class)->html($template, $subjects, 'sheet');

    expect(substr_count($html, 'class="card"'))->toBe(10)
        ->and(substr_count($html, 'class="page'))->toBe(2)
        ->and($html)->toContain('class="cut"');
});
