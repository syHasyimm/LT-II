<?php

namespace App\Http\Controllers\Pembina;

use App\Enums\GudepStatus;
use App\Enums\TemplateType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Pdf\GudepCardPrinter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Inertia\Inertia;

/** ID Card gudep sendiri dalam lembar A4 3 × 3, setelah gudep terverifikasi. */
class IdCardController extends Controller
{
    public function __invoke(Request $request, GudepCardPrinter $printer): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $gudep = $user->gudep;

        abort_unless($gudep?->status === GudepStatus::Verified, 404);

        $pdf = $printer->isReady($gudep, TemplateType::IdCard)
            ? $printer->pdf($gudep, TemplateType::IdCard, 'sheet')
            : null;

        if ($pdf === null) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'ID Card sedang disiapkan panitia. Coba lagi beberapa saat lagi.']);

            return to_route('pembina.submission.show');
        }

        return $pdf->stream(Str::slug("ID Card {$gudep->name}").'-a4.pdf');
    }
}
