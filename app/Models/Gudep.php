<?php

namespace App\Models;

use App\Enums\GudepStatus;
use App\Enums\MemberRole;
use App\Enums\TeamGroup;
use Database\Factories\GudepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Gugus depan, unit pendaftar. Satu akun pembina memiliki tepat satu gudep (BR-01).
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $gudep_number
 * @property string $institution
 * @property string $region
 * @property GudepStatus $status
 * @property string|null $admin_note
 * @property Carbon|null $deadline_extended_until
 * @property Carbon|null $revision_deadline
 * @property Carbon|null $submitted_at
 * @property Carbon|null $verified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable(['name', 'gudep_number', 'institution', 'region'])]
class Gudep extends Model
{
    /** @use HasFactory<GudepFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'status' => GudepStatus::class,
            'deadline_extended_until' => 'datetime',
            'revision_deadline' => 'datetime',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Member, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /** @return HasMany<Member, $this> */
    public function peserta(?TeamGroup $group = null): HasMany
    {
        return $this->members()
            ->where('role', MemberRole::Peserta)
            ->when($group, fn ($query) => $query->where('group', $group));
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /** @return HasMany<Member, $this> */
    public function pembina(): HasMany
    {
        return $this->members()->where('role', MemberRole::Pembina);
    }

    /** Label tampilan, misalnya "Gudep 07.041 – SMPN 3 Sleman". */
    public function displayName(): string
    {
        return trim("{$this->name} – {$this->institution}", ' –');
    }
}
