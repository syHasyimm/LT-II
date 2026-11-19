<?php

namespace App\Listeners;

use App\Events\DocumentReviewed;
use App\Models\ActivityLog;

/** Audit log peninjauan dokumen (FR-VER-06). */
class RecordDocumentReview
{
    public function handle(DocumentReviewed $event): void
    {
        $document = $event->document;

        ActivityLog::record('document.reviewed', $document->gudep, array_filter([
            'document_id' => $document->id,
            'type' => $document->type->label,
            'member' => $document->member?->name,
            'status' => $document->status->value,
            'note' => $document->note,
        ]), $event->by);
    }
}
