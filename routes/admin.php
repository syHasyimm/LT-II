<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\GenerateController;
use App\Http\Controllers\Admin\GudepController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\TemplateController;
use App\Http\Controllers\Admin\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Panel Admin
|--------------------------------------------------------------------------
|
| Halaman masuk admin disediakan Fortify di /admin/login.
|
*/

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('gudep', [GudepController::class, 'index'])->name('gudeps.index');
        Route::get('gudep-ekspor', ExportController::class)->name('gudeps.excel');
        Route::get('gudep/{gudep}', [VerificationController::class, 'show'])->name('gudeps.show');
        Route::post('gudep/{gudep}/verifikasi', [VerificationController::class, 'approve'])->name('gudeps.approve');
        Route::post('gudep/{gudep}/revisi', [VerificationController::class, 'reject'])->name('gudeps.reject');
        Route::put('gudep/{gudep}/perpanjangan', [VerificationController::class, 'extend'])->name('gudeps.extend');
        Route::post('dokumen/{document}/review', [VerificationController::class, 'review'])->name('documents.review');

        Route::get('template', [TemplateController::class, 'index'])->name('templates.index');
        Route::post('template', [TemplateController::class, 'store'])->name('templates.store');
        Route::get('template/{template}', [TemplateController::class, 'edit'])->name('templates.edit');
        Route::put('template/{template}', [TemplateController::class, 'update'])->name('templates.update');
        Route::delete('template/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');
        Route::post('template/{template}/latar', [TemplateController::class, 'uploadBackground'])->name('templates.background.upload');
        Route::get('template/{template}/latar', [TemplateController::class, 'background'])->name('templates.background');
        Route::post('template/{template}/aktifkan', [TemplateController::class, 'activate'])->name('templates.activate');
        Route::post('template/{template}/duplikat', [TemplateController::class, 'duplicate'])->name('templates.duplicate');
        Route::get('template/{template}/pratinjau', [TemplateController::class, 'preview'])->name('templates.preview');

        // ID Card & Sertifikat (FR-GEN-01..06).
        Route::get('id-card', [GenerateController::class, 'index'])->defaults('jenis', 'id-card')->name('id-cards.index');
        Route::get('sertifikat', [GenerateController::class, 'index'])->defaults('jenis', 'sertifikat')->name('certificates.index');
        Route::post('{jenis}/generate', [GenerateController::class, 'store'])->whereIn('jenis', ['id-card', 'sertifikat'])->name('generate.store');
        Route::get('{jenis}/gudep/{gudep}/pdf', [GenerateController::class, 'print'])->whereIn('jenis', ['id-card', 'sertifikat'])->name('generate.print');
        Route::get('{jenis}/zip', [GenerateController::class, 'zip'])->whereIn('jenis', ['id-card', 'sertifikat'])->name('generate.zip');
        Route::get('berkas/{generatedFile}', [GenerateController::class, 'download'])->name('generated.download');

        Route::get('jadwal', [ScheduleController::class, 'edit'])->name('schedule.edit');
        Route::put('jadwal', [ScheduleController::class, 'update'])->name('schedule.update');
    });
