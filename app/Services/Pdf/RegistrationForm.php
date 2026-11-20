<?php

namespace App\Services\Pdf;

use App\Enums\MemberRole;
use App\Enums\TeamGroup;
use App\Models\Gudep;
use App\Models\Member;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Carbon\CarbonImmutable;

/**
 * Formulir Pendaftaran satu gudep (A4 portrait) berkop Kwartir Ranting:
 * identitas gudep, peserta Putra/Putri, dan pembina pendamping.
 */
class RegistrationForm
{
    public function pdf(Gudep $gudep): DomPdf
    {
        return Pdf::loadHTML($this->html($gudep))->setPaper('a4', 'portrait');
    }

    public function html(Gudep $gudep): string
    {
        $gudep->loadMissing('user');
        $members = $gudep->members()->orderBy('id')->get();
        $peserta = fn (TeamGroup $group) => $members
            ->filter(fn (Member $member) => $member->role === MemberRole::Peserta && $member->group === $group)
            ->values();

        return view('pdf.formulir', [
            'kop' => config('camp.kop'),
            'logoLeft' => self::image((string) config('camp.kop.logo_left')),
            'logoRight' => self::image((string) config('camp.kop.logo_right')),
            'gudep' => $gudep,
            'putra' => $peserta(TeamGroup::Pa),
            'putri' => $peserta(TeamGroup::Pi),
            'pembina' => $members->filter(fn (Member $member) => $member->isPembina())->values(),
            'submittedAt' => self::date($gudep->submitted_at),
            'verifiedAt' => self::date($gudep->verified_at),
        ])->render();
    }

    /** Gambar dari public/ sebagai data URI (dompdf tanpa akses remote). */
    private static function image(string $relative): ?string
    {
        $path = public_path($relative);

        if ($relative === '' || ! is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return "data:{$mime};base64,".base64_encode((string) file_get_contents($path));
    }

    private static function date(?CarbonImmutable $value): string
    {
        return $value?->setTimezone(config('camp.timezone'))->translatedFormat('j F Y') ?? '-';
    }
}
