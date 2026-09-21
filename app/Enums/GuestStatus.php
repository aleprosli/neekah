<?php

namespace App\Enums;

enum GuestStatus: string
{
    case Attending = 'attending';
    case Declined = 'declined';
    case Opened = 'opened';
    case Shared = 'shared';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Attending => __('enums.guest_status.attending'),
            self::Declined => __('enums.guest_status.declined'),
            self::Opened => __('enums.guest_status.opened'),
            self::Shared => __('enums.guest_status.shared'),
            self::Pending => __('enums.guest_status.pending'),
        };
    }

    /** The palette key the badge components use, in Blade and in Vue. */
    public function tone(): string
    {
        return match ($this) {
            self::Attending => 'attending',
            self::Declined => 'declined',
            default => 'pending',
        };
    }
}
