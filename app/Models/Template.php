<?php

namespace App\Models;

use App\Enums\MemberRole;
use App\Enums\TemplateTarget;
use App\Enums\TemplateType;
use Carbon\CarbonImmutable;
use Database\Factories\TemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Template ID Card atau Sertifikat (FR-TPL-01..05). Disimpan sebagai data:
 * gambar latar, ukuran kertas dalam mm, dan daftar field (JSON).
 *
 * Bentuk satu field:
 *   id, kind (text|photo|qr), label, enabled, x, y, w, h (mm),
 *   content (teks dengan placeholder {nama}, {peran}, …), font (display|sans|mono),
 *   weight (400–800), italic, size (pt), color, align (left|center|right),
 *   uppercase, letter_spacing (em), bg (warna latar, opsional), radius (mm),
 *   border (warna bingkai, opsional)
 *
 * @property int $id
 * @property TemplateType $type
 * @property TemplateTarget $applies_to
 * @property string $name
 * @property string|null $background_path
 * @property float $width_mm
 * @property float $height_mm
 * @property list<array<string, mixed>> $fields
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['type', 'applies_to', 'name', 'background_path', 'width_mm', 'height_mm', 'fields', 'is_active'])]
class Template extends Model
{
    /** @use HasFactory<TemplateFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => TemplateType::class,
            'applies_to' => TemplateTarget::class,
            'width_mm' => 'float',
            'height_mm' => 'float',
            'fields' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /** Template aktif untuk jenis dan peran anggota (sasaran spesifik didahulukan). */
    public static function activeFor(TemplateType $type, MemberRole $role): ?self
    {
        $target = TemplateTarget::forRole($role);

        return self::query()
            ->where('type', $type)
            ->where('is_active', true)
            ->whereIn('applies_to', [$target, TemplateTarget::All])
            ->orderByRaw('case when applies_to = ? then 0 else 1 end', [$target->value])
            ->first();
    }

    /** Aktifkan template ini; template lain dengan jenis dan sasaran sama dinonaktifkan. */
    public function activate(): void
    {
        self::query()
            ->where('type', $this->type)
            ->where('applies_to', $this->applies_to)
            ->whereKeyNot($this->id)
            ->update(['is_active' => false]);

        $this->forceFill(['is_active' => true])->save();
    }
}
