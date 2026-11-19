<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/** Beranda publik (desain P1/P2). */
class WelcomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('welcome', [
            'rules' => [
                'pesertaPerRegu' => (int) config('camp.peserta_per_regu'),
                'pembinaMin' => (int) config('camp.pembina_min'),
                'maxUploadMb' => (int) round(config('camp.max_upload_kb') / 1024),
            ],
        ]);
    }
}
