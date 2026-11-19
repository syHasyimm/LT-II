<?php

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
    });
