<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Jejak audit (FR-VER-06): siapa, kapan, dan apa perubahannya.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $subject_type
 * @property int $subject_id
 * @property string $action
 * @property array<string, mixed>|null $meta
 * @property CarbonImmutable|null $created_at
 * @property-read User|null $user
 */
#[Fillable(['user_id', 'subject_type', 'subject_id', 'action', 'meta'])]
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function record(string $action, Model $subject, array $meta = [], ?User $by = null): self
    {
        return self::query()->create([
            'user_id' => $by !== null ? $by->id : auth()->id(),
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'action' => $action,
            'meta' => $meta,
        ]);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
