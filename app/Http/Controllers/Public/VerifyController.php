<?php

namespace App\Http\Controllers\Public;

use App\Enums\GudepStatus;
use App\Enums\MemberRole;
use App\Enums\TemplateType;
use App\Http\Controllers\Controller;
use App\Models\GeneratedFile;
use App\Services\Pdf\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifikasi publik ID Card dan Sertifikat (FR-GEN-05, BR-11, desain V1–V3).
 * Hanya menampilkan nama, gudep, peran, dan status keabsahan, tanpa data
 * sensitif (tanggal lahir, dokumen, kontak).
 */
class VerifyController extends Controller
{
    public function search(Request $request): RedirectResponse
    {
        $serial = strtoupper(trim((string) $request->query('serial')));

        if ($serial === '' || ! preg_match('/^[A-Z0-9\-]{3,40}$/', $serial)) {
            return to_route('home', ['_fragment' => 'verifikasi']);
        }

        return to_route('verify.show', $serial);
    }

    public function show(string $serial, QrCodeService $qr): Response
    {
        $serial = strtoupper($serial);

        $file = GeneratedFile::query()
            ->where('serial_number', $serial)
            ->whereNotNull('path')
            ->with('member.gudep')
            ->first();

        $valid = $file !== null && $file->member->gudep->status === GudepStatus::Verified;

        $response = Inertia::render('public/verify', [
            'serial' => $serial,
            'url' => route('verify.show', $serial),
            'result' => $valid ? [
                'type' => $file->type->value,
                'type_label' => $file->type === TemplateType::IdCard ? 'ID Card' : 'Sertifikat',
                'document_label' => ($file->type === TemplateType::IdCard ? 'ID Card ' : 'Sertifikat ').($file->member->role === MemberRole::Pembina ? 'Pembina' : 'Peserta'),
                'name' => $file->member->name,
                'gudep' => "{$file->member->gudep->name} · {$file->member->gudep->institution}",
                'role' => $file->member->role === MemberRole::Pembina
                    ? 'Pembina Pendamping'
                    : 'Peserta '.$file->member->group?->label(),
                'issued_at' => $file->created_at?->setTimezone((string) config('camp.timezone'))->translatedFormat('j F Y'),
                'qr' => $qr->pngDataUri(route('verify.show', $serial), 8),
            ] : null,
        ])->toResponse(request());

        return $valid ? $response : $response->setStatusCode(Response::HTTP_NOT_FOUND);
    }
}
