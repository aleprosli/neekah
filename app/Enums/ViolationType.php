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
            self::PaymentBypass => 'Cuba bypass pembayaran platform',
            self::CancelledBooking => 'Batalkan booking tanpa sebab',
            self::NoShow => 'Tidak hadir pada hari majlis',
            self::MisleadingListing => 'Listing atau portfolio mengelirukan',
            self::PoorService => 'Kualiti perkhidmatan tidak memuaskan',
            self::Other => 'Lain-lain',
        };
    }
}
