<?php

namespace App\Enums;

enum SignatorySource: string
{
    case Account = 'ACCOUNT';
    case Manual = 'MANUAL';

    public function label(): string
    {
        return match ($this) {
            self::Account => 'Ambil dari akun Kepala PKBM',
            self::Manual => 'Input manual untuk rapor',
        };
    }
}
