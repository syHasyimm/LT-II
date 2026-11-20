<?php

use App\Http\Controllers\Pembina\DashboardController;
use App\Http\Controllers\Pembina\DocumentController;
use App\Http\Controllers\Pembina\GudepController;
use App\Http\Controllers\Pembina\IdCardController;
use App\Http\Controllers\Pembina\MemberController;
use App\Http\Controllers\Pembina\MemberImportController;
use App\Http\Controllers\Pembina\SubmissionController;
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
        Route::get('anggota/template-impor', [MemberImportController::class, 'template'])->name('members.import.template');
        Route::get('anggota/tambah/{regu}', [MemberController::class, 'create'])->name('members.create');
        Route::get('anggota/{member}', [MemberController::class, 'edit'])->name('members.edit');
        Route::get('dokumen', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('kirim', [SubmissionController::class, 'show'])->name('submission.show');
        Route::get('id-card', IdCardController::class)->name('id-card');

        // Aksi tulis hanya dalam jadwal pendaftaran (FR-PRD-02).
        Route::middleware('registration.open')->group(function () {
            Route::put('gudep', [GudepController::class, 'update'])->name('gudep.update');
            Route::post('anggota', [MemberController::class, 'store'])->name('members.store');
            Route::post('anggota/impor', [MemberImportController::class, 'store'])->middleware('throttle:10,1')->name('members.import.store');
            Route::put('anggota/{member}', [MemberController::class, 'update'])->name('members.update');
            Route::delete('anggota/{member}', [MemberController::class, 'destroy'])->name('members.destroy');
            Route::post('dokumen', [DocumentController::class, 'store'])->name('documents.store');
            Route::post('kirim', [SubmissionController::class, 'store'])->name('submission.store');
        });
    });
