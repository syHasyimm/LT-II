<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\FindOrCreatePembinaFromGoogle;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

class GoogleController extends Controller
{
    /** Halaman masuk Pembina (desain M1). */
    public function create(Request $request): Response
    {
        return Inertia::render('public/login', [
            'status' => $request->session()->get('status'),
            'devLogin' => app()->isLocal(),
        ]);
    }

    public function redirect(): SymfonyRedirect
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, FindOrCreatePembinaFromGoogle $findOrCreate): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            return to_route('pembina.login')->withErrors([
                'google' => 'Sesi login Google kedaluwarsa. Silakan coba lagi.',
            ]);
        }

        try {
            $user = $findOrCreate->handle($googleUser);
        } catch (ValidationException $exception) {
            return to_route('pembina.login')->withErrors($exception->errors());
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('pembina.dashboard'));
    }
}
