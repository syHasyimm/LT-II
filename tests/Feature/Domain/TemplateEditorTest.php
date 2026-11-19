<?php

use App\Enums\TemplateTarget;
use App\Models\Template;
use App\Models\User;
use App\Services\Templates\DefaultTemplates;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    DefaultTemplates::install();
    $this->admin = User::factory()->admin()->create();
});

it('menampilkan daftar template dan editor', function () {
    $template = Template::query()->first();

    $this->actingAs($this->admin)
        ->get(route('admin.templates.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/templates/index')->has('templates', 3));

    $this->actingAs($this->admin)
        ->get(route('admin.templates.edit', $template))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/templates/edit')
            ->where('template.id', $template->id)
            ->has('template.fields')
            ->where('samples.0.values.nama', 'Aisyah Putri Ramadhani'));
});

it('menyimpan posisi dan gaya field sebagai data', function () {
    $template = Template::query()->where('name', 'ID Card Peserta 2026')->sole();
    $fields = $template->fields;
    $fields[0]['x'] = 12.3;
    $fields[1]['size'] = 11;
    $fields[1]['enabled'] = false;

    $this->actingAs($this->admin)
        ->put(route('admin.templates.update', $template), [
            'name' => 'ID Card Peserta (revisi)',
            'applies_to' => 'peserta',
            'fields' => $fields,
        ])
        ->assertSessionHasNoErrors();

    $template->refresh();

    expect($template->name)->toBe('ID Card Peserta (revisi)')
        ->and($template->fields[0]['x'])->toBe(12.3)
        ->and($template->fields[1]['size'])->toBe(11)
        ->and($template->fields[1]['enabled'])->toBeFalse();
});

it('menolak field dengan jenis atau warna tidak valid', function () {
    $template = Template::query()->first();

    $this->actingAs($this->admin)
        ->put(route('admin.templates.update', $template), [
            'name' => 'X',
            'applies_to' => 'all',
            'fields' => [[...DefaultTemplates::text('Nama', '{nama}', x: 1, y: 1, w: 10, h: 5), 'kind' => 'video', 'color' => 'merah']],
        ])
        ->assertSessionHasErrors(['fields.0.kind', 'fields.0.color']);
});

it('mengganti gambar latar di disk privat', function () {
    $template = Template::query()->first();
    $old = $template->background_path;

    $this->actingAs($this->admin)
        ->post(route('admin.templates.background.upload', $template), [
            'background' => UploadedFile::fake()->image('latar.png', 660, 1020),
        ])
        ->assertSessionHasNoErrors();

    $template->refresh();

    expect($template->background_path)->not->toBe($old)->toStartWith('templates/');
    Storage::disk('local')->assertExists($template->background_path);
    Storage::disk('local')->assertMissing($old);

    $this->actingAs($this->admin)->get(route('admin.templates.background', $template))->assertOk();
});

it('membuat, menduplikat, mengaktifkan, dan menghapus template', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.templates.store'), ['type' => 'certificate', 'applies_to' => 'pembina', 'name' => 'Sertifikat Pembina'])
        ->assertRedirect();

    $new = Template::query()->where('name', 'Sertifikat Pembina')->sole();
    expect($new->is_active)->toBeFalse()->and($new->width_mm)->toBe(297.0);

    $this->actingAs($this->admin)->post(route('admin.templates.activate', $new));
    expect($new->refresh()->is_active)->toBeTrue()
        ->and(Template::query()->where('name', 'Sertifikat 2026')->value('is_active'))->toBeTrue();

    $source = Template::query()->where('name', 'ID Card Peserta 2026')->sole();
    $this->actingAs($this->admin)->post(route('admin.templates.duplicate', $source));
    $copy = Template::query()->where('name', 'ID Card Peserta 2026 (salinan)')->sole();
    expect($copy->is_active)->toBeFalse()->and($copy->background_path)->not->toBe($source->background_path);

    $this->actingAs($this->admin)->delete(route('admin.templates.destroy', $source))->assertSessionHasErrors('template');
    $this->actingAs($this->admin)->delete(route('admin.templates.destroy', $copy))->assertRedirect(route('admin.templates.index'));
    expect(Template::query()->find($copy->id))->toBeNull();
});

it('menyajikan pratinjau PDF template', function () {
    $template = Template::query()->where('applies_to', TemplateTarget::All)->sole();

    $response = $this->actingAs($this->admin)->get(route('admin.templates.preview', $template));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toBe('application/pdf');
});

it('melarang pembina mengakses template', function () {
    $this->actingAs(User::factory()->pembina()->create())
        ->get(route('admin.templates.index'))
        ->assertForbidden();
});
