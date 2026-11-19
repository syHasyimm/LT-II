<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Admin awal dari config camp.seed_admin (.env ADMIN_EMAIL, ADMIN_PASSWORD). Untuk produksi,
 * gunakan `php artisan app:create-admin` agar kata sandi tidak tersimpan.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('camp.seed_admin.email');
        $password = config('camp.seed_admin.password');

        if (! $password) {
            if (! app()->isLocal()) {
                $this->command?->warn('ADMIN_PASSWORD kosong; admin tidak dibuat. Gunakan php artisan app:create-admin.');

                return;
            }

            $password = 'password';
        }

        User::query()->updateOrCreate(['email' => $email], [
            'name' => config('camp.seed_admin.name'),
            'password' => $password,
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);
    }
}
