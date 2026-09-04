<?php

namespace App\Enums;

/**
 * The Vendor Performance Point table from the kertas kerja.
 * Enquiries award nothing; only real bookings, payments and completed service do.
 */
enum PointReason: string
{
    case ProfileComplete = 'profile_complete';
    case CatalogueComplete = 'catalogue_complete';
    case PlatformBooking = 'platform_booking';
    case DepositPaid = 'deposit_paid';
    case BookingCompleted = 'booking_completed';
    case FullPayment = 'full_payment';
    case PositiveReview = 'positive_review';
    case FastResponse = 'fast_response';
    case HighCompletionRate = 'high_completion_rate';
    case ViolationPenalty = 'violation_penalty';

    public function points(): int
    {
        return match ($this) {
            self::ProfileComplete => 50,
            self::CatalogueComplete => 30,
            self::PlatformBooking => 100,
            self::DepositPaid => 100,
            self::BookingCompleted => 150,
            self::FullPayment => 150,
            self::PositiveReview => 20,
            self::FastResponse => 20,
            self::HighCompletionRate => 100,
            self::ViolationPenalty => 0,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ProfileComplete => 'Profile lengkap',
            self::CatalogueComplete => 'Catalogue lengkap',
            self::PlatformBooking => 'Booking melalui platform',
            self::DepositPaid => 'Deposit dibayar',
            self::BookingCompleted => 'Booking selesai',
            self::FullPayment => 'Full payment',
            self::PositiveReview => 'Positive review',
            self::FastResponse => 'Fast response',
            self::HighCompletionRate => 'Bonus completion rate tinggi',
            self::ViolationPenalty => 'Potongan pelanggaran',
        };
    }

    /**
     * Awarded once per vendor rather than once per booking.
     */
    public function isMilestone(): bool
    {
        return in_array($this, [self::ProfileComplete, self::CatalogueComplete, self::HighCompletionRate], true);
    }
}
