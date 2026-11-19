<?php

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Gudep;
use App\Models\Member;
use App\Models\User;
use App\Services\Files\PrivateFileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Unggah dokumen per anggota atau per gudep (FR-DOC-01). Bila sudah ada
 * dokumen aktif untuk slot yang sama, dokumen lama diarsipkan dan yang baru
 * berstatus `pending` (FR-DOC-03).
 */
class StoreDocument
{
    public function __construct(private readonly PrivateFileService $files) {}

    public function handle(Gudep $gudep, ?Member $member, DocumentType $type, UploadedFile $file, User $uploader): Document
    {
        $this->ensureSlotIsValid($gudep, $member, $type);

        $path = $this->files->store($file, "documents/{$gudep->id}");

        try {
            return DB::transaction(function () use ($gudep, $member, $type, $file, $uploader, $path) {
                Document::query()
                    ->where('gudep_id', $gudep->id)
                    ->where('member_id', $member?->id)
                    ->where('document_type_id', $type->id)
                    ->active()
                    ->lockForUpdate()
                    ->update(['archived_at' => now()]);

                return Document::query()->create([
                    'gudep_id' => $gudep->id,
                    'member_id' => $member?->id,
                    'document_type_id' => $type->id,
                    'path' => $path,
                    'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
                    'mime' => (string) $file->getMimeType(),
                    'size' => (int) $file->getSize(),
                    'status' => DocumentStatus::Pending,
                    'uploaded_by' => $uploader->id,
                ]);
            });
        } catch (\Throwable $exception) {
            $this->files->delete($path);

            throw $exception;
        }
    }

    private function ensureSlotIsValid(Gudep $gudep, ?Member $member, DocumentType $type): void
    {
        if ($type->isGudepScope() && $member !== null) {
            throw ValidationException::withMessages(['member_id' => "{$type->label} adalah dokumen gudep, bukan per anggota."]);
        }

        if (! $type->isGudepScope()) {
            if ($member === null || $member->gudep_id !== $gudep->id) {
                throw ValidationException::withMessages(['member_id' => 'Pilih anggota pemilik dokumen.']);
            }

            if (! $type->requiredFor($member->role)) {
                throw ValidationException::withMessages(['document_type_id' => "{$type->label} tidak diperlukan untuk {$member->role->label()}."]);
            }
        }
    }
}
