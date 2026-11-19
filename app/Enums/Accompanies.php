<?php

namespace App\Enums;

/** Keterangan regu yang didampingi pembina (FR-GDP-04). */
enum Accompanies: string
{
    case Pa = 'pa';
    case Pi = 'pi';
    case Keduanya = 'keduanya';

    public function label(): string
    {
        return match ($this) {
            self::Pa => 'Putra',
            self::Pi => 'Putri',
            self::Keduanya => 'Keduanya',
        };
    }
}
