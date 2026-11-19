<?php

namespace App\Actions\Verification;

use App\Enums\GudepStatus;
use App\Events\GudepStatusChanged;
use App\Models\Gudep;
use App\Models\User;
use App\Services\DocumentChecklist;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** FR-VER-03: gudep hanya bisa `verified` jika semua dokumen disetujui. */
class ApproveGudep
{
    public function handle(Gudep $gudep, User $admin): Gudep
    {
        return DB::transaction(function () use ($gudep, $admin) {
            if ($gudep->status !== GudepStatus::Submitted) {
                throw ValidationException::withMessages(['verify' => 'Hanya gudep berstatus Dikirim yang dapat diverifikasi.']);
            }

            if (! DocumentChecklist::for($gudep)->allApproved()) {
                throw ValidationException::withMessages(['verify' => 'Masih ada dokumen yang belum disetujui.']);
            }

            $from = $gudep->status;
            $gudep->forceFill([
                'status' => GudepStatus::Verified,
                'verified_at' => now(),
                'admin_note' => null,
                'revision_deadline' => null,
            ])->save();

            GudepStatusChanged::dispatch($gudep, $from, $admin);

            return $gudep;
        });
    }
}
