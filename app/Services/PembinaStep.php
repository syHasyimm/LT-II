<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Http\Request;

/**
 * Langkah alur pembina yang sedang dibuka, untuk menandai timeline.
 * Nilai: gudep, peserta_pa, peserta_pi, pembina, berkas, kirim, atau ringkasan.
 */
class PembinaStep
{
    public static function fromRequest(Request $request): string
    {
        $route = $request->route();

        return match ($route?->getName()) {
            'pembina.gudep.edit' => 'gudep',
            'pembina.members.index' => self::fromRegu((string) $request->query('tab')),
            'pembina.members.create' => self::fromRegu((string) $route->parameter('regu')),
            'pembina.members.edit' => self::fromMember($route->parameter('member')),
            'pembina.documents.index' => 'berkas',
            'pembina.submission.show' => 'kirim',
            default => 'ringkasan',
        };
    }

    private static function fromRegu(string $regu): string
    {
        return match ($regu) {
            'pi' => 'peserta_pi',
            'pembina' => 'pembina',
            default => 'peserta_pa',
        };
    }

    private static function fromMember(mixed $member): string
    {
        if (! $member instanceof Member) {
            return 'ringkasan';
        }

        return $member->isPembina() ? 'pembina' : self::fromRegu($member->group->value ?? 'pa');
    }
}
