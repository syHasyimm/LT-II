<?php

namespace App\Enums;

/** Sasaran template ID Card / Sertifikat (FR-TPL-03). */
enum TemplateTarget: string
{
    case Peserta = 'peserta';
    case Pembina = 'pembina';
    case All = 'all';

    public function label(): string
    {
        return match ($this) {
            self::Peserta => 'Peserta',
            self::Pembina => 'Pembina',
            self::All => 'Peserta & pembina',
        };
    }

    public static function forRole(MemberRole $role): self
    {
        return $role === MemberRole::Pembina ? self::Pembina : self::Peserta;
    }
}
