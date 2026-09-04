<?php

namespace App\Enums;

enum WeddingRole: string
{
    case Owner = 'owner';
    case Partner = 'partner';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Pemilik majlis',
            self::Partner => 'Pasangan',
        };
    }
}
