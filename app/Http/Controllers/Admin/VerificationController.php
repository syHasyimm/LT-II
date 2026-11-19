<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Verification\ApproveGudep;
use App\Actions\Verification\RejectGudep;
use App\Actions\Verification\ReviewDocument;
use App\Enums\DocumentStatus;
use App\Enums\GudepStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pembina\DocumentController;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Gudep;
use App\Models\User;
use App\Services\DocumentChecklist;
use App\Services\RegistrationWindow;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Panel verifikasi gudep (desain D2, FR-VER-03, FR-VER-04, FR-PRD-04/05). */
class VerificationController extends Controller
{
    public function show(Gudep $gudep, RegistrationWindow $window): Response
    {
        $checklist = DocumentChecklist::for($gudep);
        $counts = $checklist->counts();
        $owner = $gudep->pembina()->where('is_owner', true)->first();
        $local = fn (?CarbonImmutable $date) => $date?->setTimezone((string) config('camp.timezone'));

        return Inertia::render('admin/gudeps/show', [
            'gudep' => [
                'id' => $gudep->id,
                'name' => $gudep->displayName(),
                'short_name' => $gudep->name,
                'region' => $gudep->region,
                'status' => $gudep->status->value,
                'owner_name' => $owner->name ?? $gudep->user->name,
                'owner_email' => $gudep->user->email,
                'submitted_at' => $local($gudep->submitted_at)?->translatedFormat('j M Y, H.i').($gudep->submitted_at ? ' WIB' : ''),
                'admin_note' => $gudep->admin_note,
                'revision_deadline' => $local($gudep->revision_deadline)?->format('Y-m-d\TH:i'),
                'deadline_extended_until' => $local($gudep->deadline_extended_until)?->format('Y-m-d\TH:i'),
                'extension_label' => $gudep->deadline_extended_until && $gudep->deadline_extended_until->isFuture()
                    ? RegistrationWindow::label($local($gudep->deadline_extended_until))
                    : null,
            ],
            'summary' => [
                'approved' => $counts['approved'],
                'total' => $counts['total'],
                'pending' => $counts['uploaded'] - $counts['approved'],
                'rejected' => $counts['rejected'],
                'missing' => $counts['missing'],
            ],
            'canReview' => $gudep->status === GudepStatus::Submitted,
            'canVerify' => $gudep->status === GudepStatus::Submitted && $checklist->allApproved(),
            'registrationOpen' => $window->isOpen(),
            'activity' => ActivityLog::query()
                ->where('subject_type', $gudep->getMorphClass())
                ->where('subject_id', $gudep->id)
                ->with('user:id,name')
                ->latest('id')
                ->limit(30)
                ->get()
                ->map(fn (ActivityLog $log) => [
                    'id' => $log->id,
                    'text' => self::describe($log),
                    'note' => $log->meta['note'] ?? null,
                    'by' => $log->user->name ?? 'Sistem',
                    'at' => $local($log->created_at)?->translatedFormat('j M Y, H.i'),
                ]),
            ...DocumentController::matrix($gudep),
        ]);
    }

    public function review(Request $request, Document $document, ReviewDocument $review): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'note' => ['nullable', 'string', 'max:500', 'required_if:decision,rejected'],
        ], [
            'note.required_if' => 'Tuliskan alasan penolakan untuk pembina.',
        ]);

        /** @var User $admin */
        $admin = $request->user();
        $review->handle($document, DocumentStatus::from($data['decision']), $data['note'] ?? null, $admin);

        return back(303);
    }

    public function approve(Request $request, Gudep $gudep, ApproveGudep $approve): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $approve->handle($gudep, $admin);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$gudep->name} terverifikasi."]);

        return to_route('admin.gudeps.show', $gudep);
    }

    public function reject(Request $request, Gudep $gudep, RejectGudep $reject, RegistrationWindow $window): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
            'revision_deadline' => [Rule::requiredIf(! $window->isOpen()), 'nullable', 'date', 'after:now'],
        ], [
            'revision_deadline.required' => 'Pendaftaran sudah ditutup. Tentukan tenggat revisi agar pembina bisa memperbaiki data.',
        ], [
            'note' => 'catatan revisi',
            'revision_deadline' => 'tenggat revisi',
        ]);

        /** @var User $admin */
        $admin = $request->user();
        $deadline = filled($data['revision_deadline'] ?? null)
            ? CarbonImmutable::parse($data['revision_deadline'], (string) config('camp.timezone'))
            : null;

        $reject->handle($gudep, $data['note'], $deadline, $admin);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$gudep->name} dikembalikan untuk revisi."]);

        return to_route('admin.gudeps.show', $gudep);
    }

    /** Kalimat ringkas untuk riwayat aktivitas (FR-VER-06). */
    private static function describe(ActivityLog $log): string
    {
        $meta = $log->meta ?? [];
        $status = fn (?string $value) => $value ? (GudepStatus::tryFrom($value)?->label() ?? $value) : '-';

        return match ($log->action) {
            'gudep.status_changed' => 'Status: '.$status($meta['from'] ?? null).' → '.$status($meta['to'] ?? null),
            'document.reviewed' => (($meta['status'] ?? '') === 'approved' ? 'Menyetujui ' : 'Menolak ').($meta['type'] ?? 'dokumen').(isset($meta['member']) ? " · {$meta['member']}" : ''),
            'gudep.extension_updated' => isset($meta['until'])
                ? 'Perpanjangan sampai '.RegistrationWindow::label(CarbonImmutable::parse($meta['until'])->setTimezone((string) config('camp.timezone')))
                : 'Perpanjangan dihapus',
            default => $log->action,
        };
    }

    /** FR-PRD-04: perpanjangan batas pendaftaran per gudep. */
    public function extend(Request $request, Gudep $gudep): RedirectResponse
    {
        $data = $request->validate([
            'deadline_extended_until' => ['nullable', 'date', 'after:now'],
        ], [], ['deadline_extended_until' => 'batas perpanjangan']);

        $until = filled($data['deadline_extended_until'] ?? null)
            ? CarbonImmutable::parse($data['deadline_extended_until'], (string) config('camp.timezone'))
            : null;

        $gudep->forceFill(['deadline_extended_until' => $until])->save();

        ActivityLog::record('gudep.extension_updated', $gudep, ['until' => $until?->toIso8601String()], $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $until ? "Perpanjangan {$gudep->name} sampai ".RegistrationWindow::label($until).'.' : "Perpanjangan {$gudep->name} dihapus.",
        ]);

        return back(303);
    }
}
