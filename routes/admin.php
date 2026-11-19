<?php

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
        Route::inertia('/', 'admin/dashboard')->name('dashboard');

        Route::get('jadwal', [ScheduleController::class, 'edit'])->name('schedule.edit');
        Route::put('jadwal', [ScheduleController::class, 'update'])->name('schedule.update');
    });
