<?php

namespace App\Http\Controllers\Pembina;

use App\Actions\Gudep\SubmitRegistration;
use App\Enums\DocumentStatus;
use App\Enums\GudepStatus;
use App\Enums\MemberRole;
use App\Enums\TemplateType;
use App\Http\Controllers\Controller;
use App\Models\Gudep;
use App\Models\User;
use App\Services\Pdf\GudepCardPrinter;
use App\Services\RegistrationProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/** Kirim pendaftaran (desain M5) dan status setelah terkirim (desain M6). */
class SubmissionController extends Controller
{
    public function show(Request $request, GudepCardPrinter $printer): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $gudep = $user->gudep;

        if ($gudep === null) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'Isi data gudep terlebih dahulu.']);

            return to_route('pembina.gudep.edit');
        }

        if (in_array($gudep->status, [GudepStatus::Submitted, GudepStatus::Verified], true)) {
            return Inertia::render('pembina/submitted', [
                'status' => $gudep->status->value,
                'submitted_at' => $gudep->submitted_at?->translatedFormat('j F Y, H.i').' WIB',
                'idCardReady' => $gudep->status === GudepStatus::Verified
                    && $printer->isReady($gudep, TemplateType::IdCard),
                'stats' => [
                    'peserta' => $gudep->members()->where('role', MemberRole::Peserta)->count(),
                    'pembina' => $gudep->members()->where('role', MemberRole::Pembina)->count(),
                    'dokumen' => $gudep->documents()->active()->count(),
                    'disetujui' => $gudep->documents()->active()->where('status', DocumentStatus::Approved)->count(),
                ],
            ]);
        }

        $progress = RegistrationProgress::for($gudep);
        $items = $progress->checklistItems();

        return Inertia::render('pembina/submit', [
            'status' => $gudep->status->value,
            'admin_note' => $gudep->admin_note,
            'items' => $items,
            'remaining' => count(array_filter($items, fn ($item) => ! $item['ok'])),
            'canSubmit' => $user->can('update', $gudep) && $progress->issues() === [],
            'editable' => $user->can('update', $gudep),
        ]);
    }

    public function store(Request $request, SubmitRegistration $submit): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var Gudep|null $gudep */
        $gudep = $user->gudep;

        abort_if($gudep === null, 404);
        Gate::authorize('update', $gudep);

        $request->validate([
            'agreement' => ['accepted'],
        ], [
            'agreement.accepted' => 'Centang pernyataan kebenaran data dan persetujuan kebijakan privasi.',
        ]);

        $submit->handle($gudep, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pendaftaran terkirim. Data terkunci sampai diverifikasi panitia.']);

        return to_route('pembina.submission.show');
    }
}
