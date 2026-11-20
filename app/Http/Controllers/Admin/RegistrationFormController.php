<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GudepStatus;
use App\Http\Controllers\Controller;
use App\Models\Gudep;
use App\Services\Pdf\RegistrationForm;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/** Formulir Pendaftaran (PDF berkop) untuk gudep yang sudah terverifikasi. */
class RegistrationFormController extends Controller
{
    public function __invoke(Gudep $gudep, RegistrationForm $form): Response
    {
        abort_unless($gudep->status === GudepStatus::Verified, 404);

        return $form->pdf($gudep)->stream('formulir-pendaftaran-'.Str::slug($gudep->name).'.pdf');
    }
}
