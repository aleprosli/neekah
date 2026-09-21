<?php

namespace App\Enums;

enum WeddingRole: string
{
    case Owner = 'owner';
    case Partner = 'partner';

    public function label(): string
    {
        return match ($this) {
            self::Owner => __('enums.wedding_role.owner'),
            self::Partner => __('enums.wedding_role.partner'),
        };
    }
}
