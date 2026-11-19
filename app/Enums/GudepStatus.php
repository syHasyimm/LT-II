<?php

namespace App\Enums;

enum GudepStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Revision = 'revision';
    case Verified = 'verified';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Dikirim',
            self::Revision => 'Perlu revisi',
            self::Verified => 'Terverifikasi',
        };
    }

    /** Data gudep hanya boleh diubah saat draft atau revisi (FR-GDP-05, BR-08). */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Revision], true);
    }
}
