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
}
