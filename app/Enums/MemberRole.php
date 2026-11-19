<?php

namespace App\Enums;

enum MemberRole: string
{
    case Peserta = 'peserta';
    case Pembina = 'pembina_pendamping';

    public function label(): string
    {
        return match ($this) {
            self::Peserta => 'Peserta',
            self::Pembina => 'Pembina Pendamping',
        };
    }
}
