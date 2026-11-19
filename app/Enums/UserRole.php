<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Pembina = 'pembina';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Pembina => 'Pembina Pendamping',
        };
    }
}
