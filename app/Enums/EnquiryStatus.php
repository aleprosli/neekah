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
            self::Open => __('enums.enquiry_status.open'),
            self::Replied => __('enums.enquiry_status.replied'),
            self::Closed => __('enums.enquiry_status.closed'),
        };
    }

    /** The palette key the badge components use, in Blade, in Vue and in the app. */
    public function tone(): string
    {
        return match ($this) {
            self::Open => 'brand',
            self::Replied => 'emerald',
            self::Closed => 'muted',
        };
    }
}
