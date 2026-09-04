<?php

namespace App\Enums;

enum PriceUnit: string
{
    case Package = 'package';
    case Pax = 'pax';

    public function label(): string
    {
        return match ($this) {
            self::Package => 'pakej',
            self::Pax => 'pax',
        };
    }
}
