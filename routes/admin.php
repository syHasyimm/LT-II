<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GudepController;
use App\Http\Controllers\Admin\ScheduleController;
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

        Route::get('jadwal', [ScheduleController::class, 'edit'])->name('schedule.edit');
        Route::put('jadwal', [ScheduleController::class, 'update'])->name('schedule.update');
    });
