<?php

use App\Enums\TemplateType;
use App\Models\Template;
use Illuminate\Support\Facades\Storage;

it('mengganti latar semua template ID Card dan membiarkan sertifikat', function () {
    Storage::fake('local');
    $idCard = Template::factory()->create(['type' => TemplateType::IdCard, 'name' => 'Kartu A', 'background_path' => 'templates/lama.png']);
    $certificate = Template::factory()->create(['type' => TemplateType::Certificate, 'background_path' => 'templates/sertifikat.png']);

    $this->artisan('app:latar-idcard-pramuka')
        ->expectsOutputToContain('✓ Kartu A')
        ->assertSuccessful();

    $idCard->refresh();
    expect($idCard->background_path)->not->toBe('templates/lama.png')
        ->and($certificate->refresh()->background_path)->toBe('templates/sertifikat.png');
    Storage::disk('local')->assertExists($idCard->background_path);
    expect(Storage::disk('local')->get($idCard->background_path))
        ->toBe(file_get_contents(resource_path('templates/idcard-pramuka.png')));
});

it('memasang template bawaan bila tabel template kosong', function () {
    Storage::fake('local');

    $this->artisan('app:latar-idcard-pramuka')
        ->expectsOutputToContain('template bawaan dipasang')
        ->assertSuccessful();

    expect(Template::query()->where('type', TemplateType::IdCard)->count())->toBe(2);
});
