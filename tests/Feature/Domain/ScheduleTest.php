<?php

use App\Enums\GudepStatus;
use App\Models\Gudep;
use App\Models\Setting;
use App\Models\User;
use App\Services\RegistrationWindow;

$gudepPayload = [
    'name' => 'Gudep 07.041',
    'gudep_number' => '07.041',
    'institution' => 'SMP Negeri 3 Sleman',
    'region' => 'Kwarcab Sleman',
];

it('menyimpan jadwal dari dashboard admin dalam zona waktu Asia/Jakarta', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.schedule.update'), [
            'opens_at' => '2026-11-01T08:00',
            'closes_at' => '2026-12-15T23:59',
        ])
        ->assertRedirect(route('admin.schedule.edit'));

    expect(Setting::get(RegistrationWindow::OPEN_KEY))->toBe('2026-11-01T08:00:00+07:00')
        ->and(Setting::get(RegistrationWindow::CLOSE_KEY))->toBe('2026-12-15T23:59:00+07:00')
        ->and(Setting::query()->where('key', RegistrationWindow::CLOSE_KEY)->value('updated_by'))->toBe($admin->id);
});

it('menolak waktu tutup sebelum waktu buka', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.schedule.update'), [
            'opens_at' => '2026-12-15T08:00',
            'closes_at' => '2026-12-01T08:00',
        ])
        ->assertSessionHasErrors('closes_at');
});

it('melarang pembina mengubah jadwal', function () {
    $this->actingAs(User::factory()->pembina()->create())
        ->put(route('admin.schedule.update'), ['opens_at' => '2026-11-01T08:00', 'closes_at' => '2026-12-01T08:00'])
        ->assertForbidden();
});

it('menolak pembuatan gudep sebelum pendaftaran dibuka', function () use ($gudepPayload) {
    setRegistrationWindow(now()->addDay(), now()->addDays(30));
    $user = User::factory()->pembina()->create();

    $this->actingAs($user)
        ->from(route('pembina.gudep.edit'))
        ->put(route('pembina.gudep.update'), $gudepPayload)
        ->assertRedirect(route('pembina.gudep.edit'))
        ->assertSessionHasErrors('registration');

    expect(Gudep::query()->count())->toBe(0);
});

it('membuat halaman pembina read-only setelah pendaftaran ditutup', function () {
    setRegistrationWindow(now()->subDays(30), now()->subMinute());
    $gudep = Gudep::factory()->complete()->create();

    $this->actingAs($gudep->user)
        ->get(route('pembina.members.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('editable', false));

    $this->actingAs($gudep->user)
        ->delete(route('pembina.members.destroy', $gudep->peserta()->first()))
        ->assertSessionHasErrors('registration');

    expect($gudep->members()->count())->toBe(17);
});

it('tetap mengizinkan perubahan bagi gudep dengan perpanjangan aktif', function () use ($gudepPayload) {
    setRegistrationWindow(now()->subDays(30), now()->subDay());
    $gudep = Gudep::factory()->withOwner()->create(['deadline_extended_until' => now()->addDays(2)]);

    $this->actingAs($gudep->user)
        ->put(route('pembina.gudep.update'), [...$gudepPayload, 'name' => 'Gudep Perpanjangan'])
        ->assertSessionHasNoErrors();

    expect($gudep->refresh()->name)->toBe('Gudep Perpanjangan');
});

it('mengizinkan perbaikan revisi setelah tutup hanya bila ada tenggat revisi', function (?string $deadline, bool $allowed) use ($gudepPayload) {
    setRegistrationWindow(now()->subDays(30), now()->subDay());
    $gudep = Gudep::factory()->status(GudepStatus::Revision)->withOwner()->create([
        'revision_deadline' => $deadline ? now()->modify($deadline) : null,
    ]);

    $response = $this->actingAs($gudep->user)
        ->put(route('pembina.gudep.update'), [...$gudepPayload, 'name' => 'Gudep Revisi']);

    $allowed ? $response->assertSessionHasNoErrors() : $response->assertSessionHasErrors('registration');
})->with([
    'tenggat masih berlaku' => ['+2 days', true],
    'tenggat lewat' => ['-1 hour', false],
    'tanpa tenggat' => [null, false],
]);
