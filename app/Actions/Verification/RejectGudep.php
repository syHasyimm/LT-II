<?php

namespace App\Actions\Verification;

use App\Enums\GudepStatus;
use App\Events\GudepStatusChanged;
use App\Models\Gudep;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * FR-VER-04: minta revisi dengan catatan, status menjadi `revision`.
 * FR-PRD-05: bila pendaftaran sudah ditutup, tenggat revisi menentukan
 * sampai kapan pembina boleh memperbaiki.
 */
class RejectGudep
{
    public function handle(Gudep $gudep, string $note, ?CarbonImmutable $revisionDeadline, User $admin): Gudep
    {
        return DB::transaction(function () use ($gudep, $note, $revisionDeadline, $admin) {
            if ($gudep->status !== GudepStatus::Submitted) {
                throw ValidationException::withMessages(['note' => 'Hanya gudep berstatus Dikirim yang dapat dikembalikan untuk revisi.']);
            }

            $from = $gudep->status;
            $gudep->forceFill([
                'status' => GudepStatus::Revision,
                'admin_note' => trim($note),
                'revision_deadline' => $revisionDeadline,
            ])->save();

            GudepStatusChanged::dispatch($gudep, $from, $admin);

            return $gudep;
        });
    }
}
