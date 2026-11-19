<?php

namespace App\Actions\Generate;

use App\Enums\GudepStatus;
use App\Enums\TemplateType;
use App\Models\GeneratedFile;
use App\Models\Member;
use App\Models\Template;
use App\Models\User;
use App\Services\Files\PrivateFileService;
use App\Services\Pdf\RenderSubject;
use App\Services\Pdf\TemplateRenderer;
use App\Services\SerialNumberService;
use Illuminate\Validation\ValidationException;

/**
 * Buat ID Card atau Sertifikat satu anggota ke PDF di disk privat
 * (FR-GEN-01..03). Hanya untuk anggota gudep terverifikasi (BR-09).
 */
class GenerateMemberFile
{
    public function __construct(
        private readonly SerialNumberService $serials,
        private readonly TemplateRenderer $renderer,
        private readonly PrivateFileService $files,
    ) {}

    public function handle(Member $member, TemplateType $type, ?User $by = null): GeneratedFile
    {
        if ($member->gudep->status !== GudepStatus::Verified) {
            throw ValidationException::withMessages(['member' => "{$member->name}: gudep belum terverifikasi."]);
        }

        $template = Template::activeFor($type, $member->role)
            ?? throw ValidationException::withMessages(['template' => "Belum ada template {$type->label()} aktif untuk {$member->role->label()}."]);

        $file = $this->serials->assign($member, $type);
        $path = "generated/{$type->value}/{$file->serial_number}.pdf";

        $pdf = $this->renderer->pdf($template, [RenderSubject::fromMember($member, $file->serial_number)]);
        $this->files->put($path, $pdf->output());

        $file->forceFill([
            'template_id' => $template->id,
            'path' => $path,
            'generated_by' => $by?->id,
            'generated_at' => now(),
        ])->save();

        return $file;
    }
}
