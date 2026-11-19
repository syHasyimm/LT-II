<?php

use App\Http\Controllers\Pembina\DashboardController;
use App\Http\Controllers\Pembina\DocumentController;
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
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('gudep', [GudepController::class, 'edit'])->name('gudep.edit');
        Route::get('anggota', [MemberController::class, 'index'])->name('members.index');
        Route::get('anggota/tambah/{regu}', [MemberController::class, 'create'])->name('members.create');
        Route::get('anggota/{member}', [MemberController::class, 'edit'])->name('members.edit');
        Route::get('dokumen', [DocumentController::class, 'index'])->name('documents.index');

        // Aksi tulis hanya dalam jadwal pendaftaran (FR-PRD-02).
        Route::middleware('registration.open')->group(function () {
            Route::put('gudep', [GudepController::class, 'update'])->name('gudep.update');
            Route::post('anggota', [MemberController::class, 'store'])->name('members.store');
            Route::put('anggota/{member}', [MemberController::class, 'update'])->name('members.update');
            Route::delete('anggota/{member}', [MemberController::class, 'destroy'])->name('members.destroy');
            Route::post('dokumen', [DocumentController::class, 'store'])->name('documents.store');
        });
    });
