<?php

namespace App\Listeners;

use App\Events\GudepStatusChanged;
use App\Models\ActivityLog;
use App\Notifications\RegistrationStatusChanged;

/** Audit log (FR-VER-06) dan email ke pembina (FR-VER-05) saat status gudep berubah. */
class RecordGudepStatusChange
{
    public function handle(GudepStatusChanged $event): void
    {
        $gudep = $event->gudep;

        ActivityLog::record('gudep.status_changed', $gudep, array_filter([
            'from' => $event->from->value,
            'to' => $gudep->status->value,
            'note' => $gudep->admin_note,
            'revision_deadline' => $gudep->revision_deadline?->toIso8601String(),
        ]), $event->by);

        $gudep->user->notify(new RegistrationStatusChanged($gudep));
    }
}
