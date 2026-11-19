<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/** Halaman verifikasi publik ID Card dan Sertifikat (FR-GEN-05). Dilengkapi pada T6.5. */
class VerifyController extends Controller
{
    public function show(string $serial): Response
    {
        return Inertia::render('public/verify', ['serial' => $serial]);
    }
}
