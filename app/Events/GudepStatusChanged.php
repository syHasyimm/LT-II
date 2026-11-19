<?php

namespace App\Events;

use App\Enums\GudepStatus;
use App\Models\Gudep;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Status pendaftaran gudep berubah (kirim, setujui, minta revisi). Dipakai
 * untuk audit log dan email notifikasi ke pembina (FR-VER-05, FR-VER-06).
 */
class GudepStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Gudep $gudep,
        public GudepStatus $from,
        public ?User $by = null,
    ) {}
}
