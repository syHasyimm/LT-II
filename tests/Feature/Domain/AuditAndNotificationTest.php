<?php

use App\Enums\DocumentStatus;
use App\Enums\GudepStatus;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Gudep;
use App\Models\User;
use App\Notifications\RegistrationStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('mencatat perubahan status dan mengirim email ke pembina saat diminta revisi', function () {
    Notification::fake();
    $gudep = Gudep::factory()->status(GudepStatus::Submitted)->withOwner()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.gudeps.reject', $gudep), ['note' => 'Surat kesehatan buram.'])
        ->assertSessionHasNoErrors();

    $log = ActivityLog::query()->where('action', 'gudep.status_changed')->sole();

    expect($log->user_id)->toBe($this->admin->id)
        ->and($log->meta)->toMatchArray(['from' => 'submitted', 'to' => 'revision', 'note' => 'Surat kesehatan buram.']);

    Notification::assertSentTo($gudep->user, RegistrationStatusChanged::class, function ($notification, $channels) use ($gudep) {
        $mail = $notification->toMail($gudep->user);

        return $channels === ['mail']
            && str_contains($mail->subject, 'perlu revisi')
            && collect($mail->introLines)->contains(fn ($line) => str_contains($line, 'Surat kesehatan buram.'));
    });
});

it('mengirim email lewat queue', function () {
    expect(new RegistrationStatusChanged(Gudep::factory()->make()))
        ->toBeInstanceOf(ShouldQueue::class);
});

it('mencatat peninjauan dokumen beserta peninjaunya', function () {
    $gudep = Gudep::factory()->status(GudepStatus::Submitted)->withOwner()->create();
    $document = Document::factory()->for($gudep)->type('kta')->create();

    $this->actingAs($this->admin)
        ->post(route('admin.documents.review', $document), ['decision' => 'rejected', 'note' => 'KTA kedaluwarsa'])
        ->assertSessionHasNoErrors();

    $log = ActivityLog::query()->where('action', 'document.reviewed')->sole();

    expect($log->subject_id)->toBe($gudep->id)
        ->and($log->meta)->toMatchArray(['status' => DocumentStatus::Rejected->value, 'note' => 'KTA kedaluwarsa']);

    $this->actingAs($this->admin)
        ->get(route('admin.gudeps.show', $gudep))
        ->assertInertia(fn ($page) => $page->where('activity.0.note', 'KTA kedaluwarsa')->where('activity.0.by', $this->admin->name));
});

it('mencatat perubahan jadwal pendaftaran', function () {
    $this->actingAs($this->admin)->put(route('admin.schedule.update'), [
        'opens_at' => '2026-11-01T08:00',
        'closes_at' => '2026-12-15T23:59',
    ]);

    expect(ActivityLog::query()->where('action', 'settings.schedule_updated')->sole()->user_id)->toBe($this->admin->id);
});
