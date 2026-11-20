<?php

namespace App\Jobs;

use App\Actions\Generate\GenerateMemberFile;
use App\Enums\TemplateType;
use App\Models\GeneratedFile;
use App\Models\Member;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Render satu ID Card / Sertifikat di background (FR-GEN-02). */
class GenerateFileJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    /** Waktu job diantrikan; kartu yang dibuat sesudahnya tidak perlu dibuat lagi. */
    public ?string $queuedAt = null;

    public function __construct(
        public int $memberId,
        public TemplateType $type,
        public ?int $userId = null,
    ) {
        $this->queuedAt = now()->toIso8601String();
    }

    public function handle(GenerateMemberFile $generate): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        // Mis. admin menekan Generate saat antrean verifikasi masih berjalan:
        // kartu yang sudah dibuat setelah job ini diantrikan sudah terbaru.
        $alreadyRendered = $this->queuedAt !== null && GeneratedFile::query()
            ->where('member_id', $this->memberId)
            ->where('type', $this->type)
            ->whereNotNull('path')
            ->where('generated_at', '>=', CarbonImmutable::parse($this->queuedAt))
            ->exists();

        if ($alreadyRendered) {
            return;
        }

        $member = Member::query()->with('gudep')->find($this->memberId);

        if ($member === null) {
            return;
        }

        $generate->handle($member, $this->type, $this->userId ? User::query()->find($this->userId) : null);
    }
}
