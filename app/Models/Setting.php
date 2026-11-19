<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Pengaturan yang bisa diubah admin tanpa deploy, misalnya jadwal
 * pendaftaran (`registration_open_at`, `registration_close_at`).
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property int|null $updated_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['key', 'value', 'updated_by'])]
class Setting extends Model
{
    private const CACHE_KEY = 'settings.all';

    public static function get(string $key, ?string $default = null): ?string
    {
        /** @var array<string, string|null> $all */
        $all = Cache::rememberForever(self::CACHE_KEY, fn () => self::query()->pluck('value', 'key')->all());

        return $all[$key] ?? $default;
    }

    public static function set(string $key, ?string $value, ?User $by = null): void
    {
        self::query()->updateOrCreate(['key' => $key], [
            'value' => $value,
            'updated_by' => $by?->id,
        ]);

        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }
}
