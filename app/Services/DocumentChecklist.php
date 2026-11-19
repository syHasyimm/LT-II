<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Gudep;
use App\Models\Member;
use Illuminate\Support\Collection;

/**
 * Matriks kelengkapan dokumen (FR-DOC-02): anggota × jenis dokumen, beserta
 * hitungan diunggah / belum / ditolak.
 */
class DocumentChecklist
{
    /** @var Collection<int, DocumentType> */
    private Collection $types;

    /** @var Collection<string, Document> kunci: "{member_id|gudep}:{type_id}" */
    private Collection $documents;

    public function __construct(private readonly Gudep $gudep)
    {
        $this->types = DocumentType::query()->orderBy('sort')->get();
        $this->documents = $gudep->documents()->active()->get()
            ->keyBy(fn (Document $document) => ($document->member_id ?? 'gudep').':'.$document->document_type_id);
    }

    public static function for(Gudep $gudep): self
    {
        return new self($gudep);
    }

    /** @return Collection<int, DocumentType> */
    public function memberTypes(): Collection
    {
        return $this->types->reject->isGudepScope()->values();
    }

    /** @return Collection<int, DocumentType> */
    public function gudepTypes(): Collection
    {
        return $this->types->filter->isGudepScope()->values();
    }

    public function documentFor(?Member $member, DocumentType $type): ?Document
    {
        return $this->documents->get(($member->id ?? 'gudep').':'.$type->id);
    }

    /**
     * Semua slot dokumen wajib beserta dokumennya (bisa null).
     *
     * @return list<array{member: Member|null, type: DocumentType, document: Document|null}>
     */
    public function requiredSlots(): array
    {
        $slots = [];

        foreach ($this->gudepTypes() as $type) {
            $slots[] = ['member' => null, 'type' => $type, 'document' => $this->documentFor(null, $type)];
        }

        foreach ($this->gudep->members()->orderBy('id')->get() as $member) {
            foreach ($this->memberTypes() as $type) {
                if ($type->requiredFor($member->role)) {
                    $slots[] = ['member' => $member, 'type' => $type, 'document' => $this->documentFor($member, $type)];
                }
            }
        }

        return $slots;
    }

    /** @return array{uploaded: int, missing: int, rejected: int, approved: int, total: int} */
    public function counts(): array
    {
        $counts = ['uploaded' => 0, 'missing' => 0, 'rejected' => 0, 'approved' => 0, 'total' => 0];

        foreach ($this->requiredSlots() as $slot) {
            $counts['total']++;
            $status = $slot['document']?->status;

            if ($status === null) {
                $counts['missing']++;
            } elseif ($status === DocumentStatus::Rejected) {
                $counts['rejected']++;
            } else {
                $counts['uploaded']++;

                if ($status === DocumentStatus::Approved) {
                    $counts['approved']++;
                }
            }
        }

        return $counts;
    }

    /**
     * Kekurangan dokumen untuk pemeriksaan sebelum kirim (BR-07).
     *
     * @return list<string>
     */
    public function issues(): array
    {
        $issues = [];

        foreach ($this->requiredSlots() as $slot) {
            $owner = $slot['member']->name ?? 'Gudep';
            $status = $slot['document']?->status;

            if ($status === null) {
                $issues[] = "{$owner}: {$slot['type']->label} belum diunggah.";
            } elseif ($status === DocumentStatus::Rejected) {
                $issues[] = "{$owner}: {$slot['type']->label} ditolak, unggah ulang.";
            }
        }

        return $issues;
    }

    /** Semua dokumen wajib sudah disetujui admin (FR-VER-03). */
    public function allApproved(): bool
    {
        foreach ($this->requiredSlots() as $slot) {
            if ($slot['document']?->status !== DocumentStatus::Approved) {
                return false;
            }
        }

        return true;
    }
}
