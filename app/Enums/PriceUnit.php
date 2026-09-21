<?php

namespace App\Enums;

enum PriceUnit: string
{
    case Package = 'package';
    case Pax = 'pax';

    public function label(): string
    {
        return match ($this) {
            self::Package => __('enums.price_unit.package'),
            self::Pax => __('enums.price_unit.pax'),
        };
    }
}
