<?php

namespace App\Enums;

enum TemplateType: string
{
    case IdCard = 'id_card';
    case Certificate = 'certificate';

    public function label(): string
    {
        return match ($this) {
            self::IdCard => 'ID Card',
            self::Certificate => 'Sertifikat',
        };
    }
}
