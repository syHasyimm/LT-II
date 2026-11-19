<?php

namespace App\Actions\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * FR-AUTH-01: login pertama membuat user role=pembina, login berikutnya
 * masuk ke akun yang sama (dicocokkan lewat google_id).
 * FR-AUTH-02: akun Google tidak bisa menjadi admin.
 */
class FindOrCreatePembinaFromGoogle
{
    public function handle(SocialiteUser $googleUser): User
    {
        $user = User::query()->where('google_id', $googleUser->getId())->first();

        if ($user === null && $googleUser->getEmail() !== null) {
            $user = User::query()->where('email', $googleUser->getEmail())->first();

            if ($user !== null && ! $user->isAdmin()) {
                $user->google_id = (string) $googleUser->getId();
            }
        }

        if ($user?->isAdmin()) {
            throw ValidationException::withMessages([
                'google' => 'Email ini terdaftar sebagai admin. Admin masuk lewat halaman Masuk Admin.',
            ]);
        }

        if ($user === null) {
            if ($googleUser->getEmail() === null) {
                throw ValidationException::withMessages([
                    'google' => 'Akun Google Anda tidak membagikan alamat email.',
                ]);
            }

            $user = new User([
                'name' => $googleUser->getName() ?: $googleUser->getEmail(),
                'email' => $googleUser->getEmail(),
                'google_id' => (string) $googleUser->getId(),
                'role' => UserRole::Pembina,
                'email_verified_at' => now(),
            ]);
        }

        $user->avatar = $googleUser->getAvatar() ?? $user->avatar;
        $user->save();

        return $user;
    }
}
