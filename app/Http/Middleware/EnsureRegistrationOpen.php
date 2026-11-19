<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\RegistrationWindow;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * FR-PRD-02: setiap aksi tulis pembina dibandingkan dengan jadwal di tabel
 * `settings`, tanpa penjadwal. Di luar jadwal (dan tanpa perpanjangan atau
 * tenggat revisi), perubahan ditolak dengan pesan yang jelas.
 */
class EnsureRegistrationOpen
{
    public function __construct(private readonly RegistrationWindow $window) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || $this->window->allowsChangesFor($user->gudep)) {
            return $next($request);
        }

        $message = $this->window->closedMessage();

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            abort(Response::HTTP_FORBIDDEN, $message);
        }

        Inertia::flash('toast', ['type' => 'error', 'message' => $message]);

        return back(Response::HTTP_SEE_OTHER)->withErrors(['registration' => $message]);
    }
}
