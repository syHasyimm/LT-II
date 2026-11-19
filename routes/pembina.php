<?php

use App\Http\Controllers\Pembina\GudepController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Area Pembina Pendamping
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:pembina'])
    ->prefix('pembina')
    ->name('pembina.')
    ->group(function () {
        Route::inertia('/', 'pembina/dashboard')->name('dashboard');

        Route::get('gudep', [GudepController::class, 'edit'])->name('gudep.edit');
        Route::put('gudep', [GudepController::class, 'update'])->name('gudep.update');

        // Diisi pada T2.3–T2.4.
        Route::inertia('anggota', 'pembina/dashboard')->name('members.index');
    });
