<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Dokumen yang diunggah pembina (BR-04). Hanya satu dokumen aktif per
 * jenis per anggota/gudep; dokumen lama diarsipkan (FR-DOC-03).
 *
 * @property int $id
 * @property int $gudep_id
 * @property int|null $member_id
 * @property int $document_type_id
 * @property string $path
 * @property string $original_name
 * @property string $mime
 * @property int $size
 * @property DocumentStatus $status
 * @property string|null $note
 * @property int|null $uploaded_by
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Gudep $gudep
 * @property-read Member|null $member
 * @property-read DocumentType $type
 */
#[Fillable(['gudep_id', 'member_id', 'document_type_id', 'path', 'original_name', 'mime', 'size', 'status', 'note', 'uploaded_by'])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'reviewed_at' => 'datetime',
            'archived_at' => 'datetime',
            'size' => 'integer',
        ];
    }

    /** @param Builder<Document> $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /** @return BelongsTo<Gudep, $this> */
    public function gudep(): BelongsTo
    {
        return $this->belongsTo(Gudep::class);
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** @return BelongsTo<DocumentType, $this> */
    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function sizeLabel(): string
    {
        return $this->size >= 1048576
            ? number_format($this->size / 1048576, 1, ',', '.').' MB'
            : max(1, (int) round($this->size / 1024)).' KB';
    }
}
