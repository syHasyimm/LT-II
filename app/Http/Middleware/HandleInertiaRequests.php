<?php

namespace App\Http\Middleware;

use App\Enums\GudepStatus;
use App\Models\Gudep;
use App\Services\PembinaStep;
use App\Services\RegistrationProgress;
use App\Services\RegistrationWindow;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'registration' => fn () => app(RegistrationWindow::class)->toArray(),
            'adminCounters' => fn () => $request->user()?->isAdmin()
                ? ['submitted' => Gudep::query()->where('status', GudepStatus::Submitted)->count()]
                : null,
            // Alur pembina per langkah: data timeline dan langkah halaman aktif.
            'pembinaSteps' => fn () => $request->user()?->isPembina()
                ? RegistrationProgress::for($request->user()->gudep)->steps()
                : null,
            'pembinaStep' => fn () => $request->user()?->isPembina()
                ? PembinaStep::fromRequest($request)
                : null,
        ];
    }
}
