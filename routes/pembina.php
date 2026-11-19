<?php

use App\Http\Controllers\Pembina\GudepController;
use App\Http\Controllers\Pembina\MemberController;
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

        Route::get('anggota', [MemberController::class, 'index'])->name('members.index');
        Route::get('anggota/tambah/{regu}', [MemberController::class, 'create'])->name('members.create');
        Route::post('anggota', [MemberController::class, 'store'])->name('members.store');
        Route::get('anggota/{member}', [MemberController::class, 'edit'])->name('members.edit');
        Route::put('anggota/{member}', [MemberController::class, 'update'])->name('members.update');
        Route::delete('anggota/{member}', [MemberController::class, 'destroy'])->name('members.destroy');
    });
