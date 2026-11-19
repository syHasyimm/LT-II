<?php

namespace App\Models;

use App\Enums\TemplateType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ID Card atau Sertifikat milik satu anggota (FR-GEN-03). Nomor seri
 * diberikan saat pertama dibuat dan dipertahankan saat generate ulang.
 *
 * @property int $id
 * @property int $member_id
 * @property int|null $template_id
 * @property TemplateType $type
 * @property string $serial_number
 * @property int $sequence
 * @property string|null $path
 * @property int|null $generated_by
 * @property CarbonImmutable|null $generated_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Member $member
 * @property-read Template|null $template
 */
#[Fillable(['member_id', 'template_id', 'type', 'serial_number', 'sequence', 'path', 'generated_by', 'generated_at'])]
class GeneratedFile extends Model
{
    protected function casts(): array
    {
        return [
            'type' => TemplateType::class,
            'generated_at' => 'datetime',
            'sequence' => 'integer',
        ];
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** @return BelongsTo<Template, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
