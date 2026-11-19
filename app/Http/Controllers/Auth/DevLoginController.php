<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Masuk sebagai pembina demo tanpa Google. Rute ini hanya didaftarkan saat
 * APP_ENV=local (lihat routes/web.php), untuk mencoba aplikasi sebelum
 * kredensial Google OAuth tersedia.
 */
class DevLoginController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless(app()->isLocal(), 404);

        $user = User::query()->firstOrCreate(
            ['email' => 'pembina.demo@perkemahan-lt2.test'],
            [
                'name' => 'Kak Pembina Demo',
                'google_id' => 'dev-pembina-demo',
                'role' => UserRole::Pembina,
                'email_verified_at' => now(),
            ],
        );

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return to_route('pembina.dashboard');
    }
}
