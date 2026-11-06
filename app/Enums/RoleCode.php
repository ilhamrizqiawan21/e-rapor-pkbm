<?php

namespace App\Enums;

enum RoleCode: string
{
    case Admin = 'ADMIN';
    case Guru = 'GURU';
    case Kepsek = 'KEPSEK';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Guru => 'Guru',
            self::Kepsek => 'Kepala PKBM',
        };
    }
}
