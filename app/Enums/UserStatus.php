<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'ACTIVE';
    case Inactive = 'INACTIVE';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Inactive => 'Nonaktif',
        };
    }
}
