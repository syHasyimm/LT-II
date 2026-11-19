<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * FR-AUTH-03: pembina yang membuka /admin/* mendapat 403, admin yang membuka
 * area pembina diarahkan ke dashboard admin.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $required = UserRole::from($role);

        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        if ($user->role === $required) {
            return $next($request);
        }

        if ($required === UserRole::Pembina && $user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        abort(Response::HTTP_FORBIDDEN, 'Halaman ini khusus admin panitia.');
    }
}
