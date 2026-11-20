<?php

namespace App\Actions\Verification;

use App\Actions\Generate\QueueGudepIdCards;
use App\Enums\GudepStatus;
use App\Events\GudepStatusChanged;
use App\Models\Gudep;
use App\Models\User;
use App\Services\DocumentChecklist;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * FR-VER-03: gudep hanya bisa `verified` jika semua dokumen disetujui.
 * Setelah terverifikasi, ID Card seluruh anggota diantrikan otomatis.
 */
class ApproveGudep
{
    public function __construct(private readonly QueueGudepIdCards $idCards) {}

    /** @return int jumlah anggota yang ID Card-nya diantrikan */
    public function handle(Gudep $gudep, User $admin): int
    {
        DB::transaction(function () use ($gudep, $admin) {
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
        });

        // Di luar transaksi: job baru berjalan setelah status tersimpan.
        // Gagal mengantrikan tidak membatalkan verifikasi; admin bisa membuat
        // ID Card lewat menu ID Card.
        try {
            return $this->idCards->handle($gudep, $admin);
        } catch (Throwable $exception) {
            report($exception);

            return 0;
        }
    }
}
