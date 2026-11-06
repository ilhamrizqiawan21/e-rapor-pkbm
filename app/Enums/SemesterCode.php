<?php

namespace App\Enums;

enum SemesterCode: string
{
    case Ganjil = 'GANJIL';
    case Genap = 'GENAP';

    public function label(): string
    {
        return match ($this) {
            self::Ganjil => 'Semester Ganjil',
            self::Genap => 'Semester Genap',
        };
    }

    public function defaultSequence(): int
    {
        return match ($this) {
            self::Ganjil => 1,
            self::Genap => 2,
        };
    }
}
