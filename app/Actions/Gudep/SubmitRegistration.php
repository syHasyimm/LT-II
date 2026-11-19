<?php

namespace App\Actions\Gudep;

use App\Enums\GudepStatus;
use App\Events\GudepStatusChanged;
use App\Models\Gudep;
use App\Models\User;
use App\Services\RegistrationProgress;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * FR-VER-01, BR-07, BR-08: kirim pendaftaran hanya bila kuota, pembina,
 * pas foto, dan semua dokumen wajib lengkap. Setelah dikirim data terkunci.
 */
class SubmitRegistration
{
    public function handle(Gudep $gudep, User $by): Gudep
    {
        return DB::transaction(function () use ($gudep, $by) {
            /** @var Gudep $locked */
            $locked = Gudep::query()->whereKey($gudep->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->isEditable()) {
                throw ValidationException::withMessages(['submit' => 'Pendaftaran sudah dikirim.']);
            }

            $issues = RegistrationProgress::for($locked)->issues();

            if ($issues !== []) {
                throw ValidationException::withMessages(['submit' => $issues]);
            }

            $from = $locked->status;
            $locked->status = GudepStatus::Submitted;
            $locked->submitted_at = now();
            $locked->revision_deadline = null;
            $locked->save();

            GudepStatusChanged::dispatch($locked, $from, $by);

            return $locked;
        });
    }
}
