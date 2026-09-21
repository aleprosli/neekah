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
            self::Family => __('enums.guest_group.family'),
            self::Friends => __('enums.guest_group.friends'),
            self::Work => __('enums.guest_group.work'),
            self::Neighbours => __('enums.guest_group.neighbours'),
            self::Other => __('enums.guest_group.other'),
        };
    }
}
