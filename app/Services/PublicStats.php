<?php

namespace App\Services;

use App\Enums\MemberRole;
use App\Models\Gudep;
use App\Models\Member;

/** Statistik ringkas untuk beranda publik (tanpa data pribadi). */
class PublicStats
{
    /** @return array{gudep: int, peserta: int, pembina: int} */
    public function toArray(): array
    {
        return [
            'gudep' => Gudep::query()->count(),
            'peserta' => Member::query()->where('role', MemberRole::Peserta)->count(),
            'pembina' => Member::query()->where('role', MemberRole::Pembina)->count(),
        ];
    }
}
