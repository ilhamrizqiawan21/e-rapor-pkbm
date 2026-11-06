<?php

namespace App\Enums;

enum DescriptionSnippetVisibility: string
{
    case Private = 'PRIVATE';
    case Shared = 'SHARED';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Pribadi',
            self::Shared => 'Dibagikan',
        };
    }
}
