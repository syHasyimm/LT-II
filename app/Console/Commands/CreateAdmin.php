<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('app:create-admin {--name= : Nama admin} {--email= : Email admin} {--password= : Kata sandi (jangan dipakai di server bersama)}')]
#[Description('Membuat akun admin panitia (login email + kata sandi di /admin/login)')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $data = [
            'name' => $this->option('name') ?: text('Nama admin', required: true),
            'email' => $this->option('email') ?: text('Email admin', required: true),
            'password' => $this->option('password') ?: password('Kata sandi (minimal 8 karakter)', required: true),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $data['password'],
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $this->info("Admin {$user->email} berhasil dibuat. Masuk di ".url('/admin/login'));

        return self::SUCCESS;
    }
}
