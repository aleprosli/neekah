<?php

namespace App\Enums;

enum GuestSide: string
{
    case Bride = 'bride';
    case Groom = 'groom';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Bride => 'Pihak perempuan',
            self::Groom => 'Pihak lelaki',
            self::Both => 'Kedua-dua pihak',
        };
    }
}
