<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateScheduleRequest;
use App\Models\Gudep;
use App\Models\Setting;
use App\Models\User;
use App\Services\RegistrationWindow;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** FR-PRD-01: admin mengatur jadwal buka/tutup pendaftaran tanpa deploy. */
class ScheduleController extends Controller
{
    public function edit(RegistrationWindow $window): Response
    {
        return Inertia::render('admin/schedule', [
            'opens_at' => $window->opensAt()?->format('Y-m-d\TH:i'),
            'closes_at' => $window->closesAt()?->format('Y-m-d\TH:i'),
            'extensions' => Gudep::query()
                ->whereNotNull('deadline_extended_until')
                ->where('deadline_extended_until', '>', now())
                ->orderBy('deadline_extended_until')
                ->get()
                ->map(fn (Gudep $gudep) => [
                    'id' => $gudep->id,
                    'name' => $gudep->displayName(),
                    'until' => RegistrationWindow::label(CarbonImmutable::parse($gudep->deadline_extended_until)->setTimezone((string) config('camp.timezone'))),
                ]),
        ]);
    }

    public function update(UpdateScheduleRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $timezone = (string) config('camp.timezone');

        foreach ([RegistrationWindow::OPEN_KEY => 'opens_at', RegistrationWindow::CLOSE_KEY => 'closes_at'] as $key => $field) {
            $value = CarbonImmutable::parse($request->string($field)->toString(), $timezone);
            Setting::set($key, $value->toIso8601String(), $user);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jadwal pendaftaran disimpan.']);

        return to_route('admin.schedule.edit');
    }
}
