<?php

namespace App\Enums;

enum ViolationType: string
{
    case PaymentBypass = 'payment_bypass';
    case CancelledBooking = 'cancelled_booking';
    case NoShow = 'no_show';
    case MisleadingListing = 'misleading_listing';
    case PoorService = 'poor_service';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PaymentBypass => __('enums.violation_type.payment_bypass'),
            self::CancelledBooking => __('enums.violation_type.cancelled_booking'),
            self::NoShow => __('enums.violation_type.no_show'),
            self::MisleadingListing => __('enums.violation_type.misleading_listing'),
            self::PoorService => __('enums.violation_type.poor_service'),
            self::Other => __('enums.violation_type.other'),
        };
    }
}
