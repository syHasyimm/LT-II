<?php

namespace App\Enums;

enum TeamGroup: string
{
    case Pa = 'pa';
    case Pi = 'pi';

    public function label(): string
    {
        return match ($this) {
            self::Pa => 'Putra',
            self::Pi => 'Putri',
        };
    }
}
