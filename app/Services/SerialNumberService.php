<?php

namespace App\Services;

use App\Enums\TemplateType;
use App\Models\GeneratedFile;
use App\Models\Member;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Nomor seri `LT2-<tahun>-ID-0001` / `LT2-<tahun>-CERT-0001` (BR-14).
 * Berurutan per jenis, diberikan saat pertama kali dibuat, dan tetap sama
 * saat generate ulang. Penghitung dikunci agar aman dari duplikasi walau
 * beberapa job berjalan bersamaan (FR-GEN-03).
 */
class SerialNumberService
{
    public function assign(Member $member, TemplateType $type): GeneratedFile
    {
        $existing = $this->find($member, $type);

        if ($existing !== null) {
            return $existing;
        }

        try {
            return DB::transaction(function () use ($member, $type) {
                DB::table('serial_counters')->insertOrIgnore([
                    'type' => $type->value,
                    'last' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $last = (int) DB::table('serial_counters')->where('type', $type->value)->lockForUpdate()->value('last');
                $next = $last + 1;

                DB::table('serial_counters')->where('type', $type->value)->update(['last' => $next, 'updated_at' => now()]);

                return GeneratedFile::query()->create([
                    'member_id' => $member->id,
                    'type' => $type,
                    'sequence' => $next,
                    'serial_number' => self::format($type, $next),
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            // Job lain lebih dulu memberi nomor untuk anggota ini.
            return $this->find($member, $type) ?? throw new \RuntimeException('Gagal memberi nomor seri.');
        }
    }

    public static function format(TemplateType $type, int $sequence): string
    {
        return sprintf(
            '%s-%d-%s-%04d',
            config('camp.serial_prefix'),
            config('camp.event_year'),
            $type === TemplateType::IdCard ? 'ID' : 'CERT',
            $sequence,
        );
    }

    private function find(Member $member, TemplateType $type): ?GeneratedFile
    {
        return GeneratedFile::query()->where('member_id', $member->id)->where('type', $type)->first();
    }
}
