<?php

namespace App\Enums;

enum EnquiryStatus: string
{
    case Open = 'open';
    case Replied = 'replied';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Baru',
            self::Replied => 'Dibalas',
            self::Closed => 'Ditutup',
        };
    }
}
