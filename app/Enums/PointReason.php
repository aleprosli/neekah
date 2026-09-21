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
            self::ProfileComplete => __('enums.point_reason.profile_complete'),
            self::CatalogueComplete => __('enums.point_reason.catalogue_complete'),
            self::PlatformBooking => __('enums.point_reason.platform_booking'),
            self::DepositPaid => __('enums.point_reason.deposit_paid'),
            self::BookingCompleted => __('enums.point_reason.booking_completed'),
            self::FullPayment => __('enums.point_reason.full_payment'),
            self::PositiveReview => __('enums.point_reason.positive_review'),
            self::FastResponse => __('enums.point_reason.fast_response'),
            self::HighCompletionRate => __('enums.point_reason.high_completion_rate'),
            self::ViolationPenalty => __('enums.point_reason.violation_penalty'),
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
