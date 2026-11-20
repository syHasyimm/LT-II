<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\PublicStats;
use Inertia\Inertia;
use Inertia\Response;

/** Beranda publik (desain P1/P2). */
class WelcomeController extends Controller
{
    public function __invoke(PublicStats $stats): Response
    {
        return Inertia::render('welcome', [
            'rules' => [
                'pesertaPerRegu' => (int) config('camp.peserta_per_regu'),
                'pembinaMin' => (int) config('camp.pembina_min'),
                'maxUploadMb' => (int) round(config('camp.max_upload_kb') / 1024),
                'maxPhotoMb' => (int) round(config('camp.max_photo_kb') / 1024),
            ],
            'stats' => $stats->toArray(),
        ]);
    }
}
