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
            self::Bride => __('enums.guest_side.bride'),
            self::Groom => __('enums.guest_side.groom'),
            self::Both => __('enums.guest_side.both'),
        };
    }
}
