<?php

use App\Http\Controllers\Auth\DevLoginController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\MemberPhotoController;
use App\Http\Controllers\Public\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('home');

/*
|--------------------------------------------------------------------------
| Masuk Pembina lewat Google (FR-AUTH-01)
|--------------------------------------------------------------------------
|
| Admin masuk lewat Fortify di /admin/login (lihat config/fortify.php).
|
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [GoogleController::class, 'create'])->name('pembina.login');
    Route::get('auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
    Route::get('auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

    if (app()->isLocal()) {
        Route::get('dev/login-pembina', DevLoginController::class)->name('dev.login');
    }
});

require __DIR__.'/pembina.php';
require __DIR__.'/admin.php';
require __DIR__.'/settings.php';

Route::middleware('auth')->group(function () {
    Route::get('files/foto-anggota/{member}', MemberPhotoController::class)->name('members.photo');
});
