<?php

namespace App\Enums;

enum ViolationStatus: string
{
    case Open = 'open';
    case Upheld = 'upheld';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Menunggu semakan',
            self::Upheld => 'Disahkan',
            self::Dismissed => 'Ditolak',
        };
    }
}
