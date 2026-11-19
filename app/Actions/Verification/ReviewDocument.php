<?php

namespace App\Actions\Verification;

use App\Enums\DocumentStatus;
use App\Enums\GudepStatus;
use App\Events\DocumentReviewed;
use App\Models\Document;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/** FR-VER-03: admin menyetujui atau menolak satu dokumen (tolak wajib catatan). */
class ReviewDocument
{
    public function handle(Document $document, DocumentStatus $decision, ?string $note, User $admin): Document
    {
        if ($document->archived_at !== null) {
            throw ValidationException::withMessages(['document' => 'Dokumen ini sudah diganti pembina.']);
        }

        if ($document->gudep->status !== GudepStatus::Submitted) {
            throw ValidationException::withMessages(['document' => 'Dokumen hanya dapat diperiksa saat pendaftaran berstatus Dikirim.']);
        }

        if ($decision === DocumentStatus::Rejected && blank($note)) {
            throw ValidationException::withMessages(['note' => 'Tuliskan alasan penolakan untuk pembina.']);
        }

        $document->forceFill([
            'status' => $decision,
            'note' => $decision === DocumentStatus::Rejected ? trim((string) $note) : null,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ])->save();

        DocumentReviewed::dispatch($document, $admin);

        return $document;
    }
}
