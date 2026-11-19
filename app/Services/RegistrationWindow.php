<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\CarbonImmutable;

/**
 * Jadwal buka/tutup pendaftaran (FR-PRD-01, FR-PRD-02). Nilai disimpan di
 * tabel `settings` dan dibandingkan dengan waktu sekarang pada setiap aksi,
 * tanpa penjadwal.
 */
class RegistrationWindow
{
    public const OPEN_KEY = 'registration_open_at';

    public const CLOSE_KEY = 'registration_close_at';

    public function opensAt(): ?CarbonImmutable
    {
        return $this->read(self::OPEN_KEY);
    }

    public function closesAt(): ?CarbonImmutable
    {
        return $this->read(self::CLOSE_KEY);
    }

    /** @return 'not_open'|'open'|'closed' */
    public function state(?CarbonImmutable $now = null): string
    {
        $now ??= CarbonImmutable::now($this->timezone());
        $opensAt = $this->opensAt();
        $closesAt = $this->closesAt();

        if ($opensAt === null || $now->lt($opensAt)) {
            return 'not_open';
        }

        if ($closesAt !== null && $now->gte($closesAt)) {
            return 'closed';
        }

        return 'open';
    }

    public function isOpen(?CarbonImmutable $now = null): bool
    {
        return $this->state($now) === 'open';
    }

    /**
     * @return array{state: string, opens_at: string|null, closes_at: string|null, opens_label: string|null, closes_label: string|null, now: string}
     */
    public function toArray(): array
    {
        $opensAt = $this->opensAt();
        $closesAt = $this->closesAt();

        return [
            'state' => $this->state(),
            'opens_at' => $opensAt?->toIso8601String(),
            'closes_at' => $closesAt?->toIso8601String(),
            'opens_label' => $opensAt ? self::label($opensAt) : null,
            'closes_label' => $closesAt ? self::label($closesAt) : null,
            'now' => CarbonImmutable::now($this->timezone())->toIso8601String(),
        ];
    }

    /** Contoh: "15 Des 2026, 23.59 WIB". */
    public static function label(CarbonImmutable $date): string
    {
        return $date->translatedFormat('j M Y, H.i').' WIB';
    }

    private function read(string $key): ?CarbonImmutable
    {
        $value = Setting::get($key);

        return $value ? CarbonImmutable::parse($value)->setTimezone($this->timezone()) : null;
    }

    private function timezone(): string
    {
        return (string) config('camp.timezone');
    }
}
