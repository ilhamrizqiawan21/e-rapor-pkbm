<?php

namespace App\Enums;

enum AcademicYearStatus: string
{
    case Draft = 'DRAFT';
    case Active = 'ACTIVE';
    case Closed = 'CLOSED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Aktif',
            self::Closed => 'Ditutup',
        };
    }
}
