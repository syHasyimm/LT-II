<?php

namespace App\Actions\Generate;

use App\Enums\TemplateType;
use App\Jobs\GenerateFileJob;
use App\Models\ActivityLog;
use App\Models\Gudep;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

/**
 * Antrikan pembuatan ID Card seluruh anggota gudep yang baru diverifikasi,
 * agar pembina bisa langsung mengunduhnya. Anggota yang perannya belum punya
 * template ID Card aktif dilewati; admin bisa membuatnya nanti.
 */
class QueueGudepIdCards
{
    /** @return int jumlah anggota yang diantrikan */
    public function handle(Gudep $gudep, ?User $by = null): int
    {
        $members = $gudep->members()
            ->whereIn('role', Template::activeRoles(TemplateType::IdCard))
            ->pluck('id');

        if ($members->isEmpty()) {
            return 0;
        }

        $batch = Bus::batch($members->map(fn (int $id) => new GenerateFileJob($id, TemplateType::IdCard, $by?->id))->all())
            ->name("ID Card · {$gudep->name}")
            ->allowFailures()
            ->dispatch();

        if ($by !== null) {
            ActivityLog::record('generate.started', $by, [
                'type' => TemplateType::IdCard->value,
                'scope' => 'gudep',
                'count' => $members->count(),
                'batch' => $batch->id,
            ], $by);
        }

        return $members->count();
    }
}
