<?php

namespace App\Enums;

enum GuestGroup: string
{
    case Family = 'family';
    case Friends = 'friends';
    case Work = 'work';
    case Neighbours = 'neighbours';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Family => 'Keluarga',
            self::Friends => 'Kawan',
            self::Work => 'Kerja',
            self::Neighbours => 'Jiran',
            self::Other => 'Lain-lain',
        };
    }
}
