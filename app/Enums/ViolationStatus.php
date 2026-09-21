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
            self::Open => __('enums.violation_status.open'),
            self::Upheld => __('enums.violation_status.upheld'),
            self::Dismissed => __('enums.violation_status.dismissed'),
        };
    }
}
