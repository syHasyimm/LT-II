<?php

namespace App\Jobs;

use App\Actions\Generate\GenerateMemberFile;
use App\Enums\TemplateType;
use App\Models\Member;
use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Render satu ID Card / Sertifikat di background (FR-GEN-02). */
class GenerateFileJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public int $memberId,
        public TemplateType $type,
        public ?int $userId = null,
    ) {}

    public function handle(GenerateMemberFile $generate): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $member = Member::query()->with('gudep')->find($this->memberId);

        if ($member === null) {
            return;
        }

        $generate->handle($member, $this->type, $this->userId ? User::query()->find($this->userId) : null);
    }
}
