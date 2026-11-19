<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentStatus;
use App\Enums\MemberRole;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Gudep;
use App\Models\Member;
use App\Services\Admin\GudepTable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Dashboard admin (desain D1, FR-DSH-01). */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $counts = GudepTable::statusCounts();

        return Inertia::render('admin/dashboard', [
            'today' => now()->translatedFormat('l, j F Y'),
            'counts' => $counts,
            'summary' => [
                'new_this_week' => Gudep::query()->where('created_at', '>=', now()->startOfWeek())->count(),
                'pending_documents' => Document::query()->active()->where('status', DocumentStatus::Pending)
                    ->whereHas('gudep', fn ($query) => $query->where('status', 'submitted'))->count(),
                'peserta' => Member::query()->where('role', MemberRole::Peserta)->count(),
                'pembina' => Member::query()->where('role', MemberRole::Pembina)->count(),
                'extensions' => Gudep::query()->where('deadline_extended_until', '>', now())->count(),
            ],
            // FR-AUTH-05: 2FA opsional, tetapi dashboard mengingatkan admin yang belum mengaktifkannya.
            'twoFactorEnabled' => (bool) $request->user()?->hasEnabledTwoFactorAuthentication(),
            ...GudepTable::fromRequest($request, 8),
        ]);
    }
}
