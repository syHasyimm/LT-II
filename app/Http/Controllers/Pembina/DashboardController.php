<?php

namespace App\Http\Controllers\Pembina;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RegistrationProgress;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Beranda Pembina (desain M2). */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $gudep = $user->gudep;
        $progress = RegistrationProgress::for($gudep);

        return Inertia::render('pembina/dashboard', [
            'gudep' => $gudep ? [
                'name' => $gudep->displayName(),
                'region' => $gudep->region,
                'status' => $gudep->status->value,
                'admin_note' => $gudep->admin_note,
            ] : null,
            'percent' => $progress->percent(),
            'steps' => $progress->steps(),
        ]);
    }
}
