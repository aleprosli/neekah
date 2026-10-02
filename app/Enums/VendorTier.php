<?php

namespace App\Enums;

enum VendorTier: string
{
    case New = 'new';
    case Verified = 'verified';
    case Trusted = 'trusted';
    case Top = 'top';
    case Recommended = 'recommended';

    public function label(): string
    {
        return match ($this) {
            self::New => __('enums.vendor_tier.new'),
            self::Verified => __('enums.vendor_tier.verified'),
            self::Trusted => __('enums.vendor_tier.trusted'),
            self::Top => __('enums.vendor_tier.top'),
            self::Recommended => __('enums.vendor_tier.recommended'),
        };
    }

    /**
     * What a vendor needs to earn this tier — the one place the ladder is
     * written down (RecalculateVendorStats decides with it, TierProgress and
     * the ranking card explain it). Null for New and Verified, which come
     * from approval alone.
     *
     * Owner, 2 Oct 2026: couples are sent to the vendor's WhatsApp and book
     * there, so bookings are not recorded and a ladder built on them left
     * almost every vendor at Verified. It climbs on what Neekah does see: a
     * complete profile, reviews on the profile (never ones the vendor added
     * themselves) and their rating; Elite also needs a clean record.
     *
     * @return array{complete_profile: bool, reviews: int, rating: float, clean_record: bool}|null
     */
    public function requirements(): ?array
    {
        return match ($this) {
            self::New, self::Verified => null,
            self::Trusted => ['complete_profile' => true, 'reviews' => 3, 'rating' => 4.0, 'clean_record' => false],
            self::Top => ['complete_profile' => true, 'reviews' => 8, 'rating' => 4.5, 'clean_record' => false],
            self::Recommended => ['complete_profile' => true, 'reviews' => 15, 'rating' => 4.7, 'clean_record' => true],
        };
    }

    /**
     * Position in the ranking ladder, 0 for New up to 4 for Recommended.
     */
    public function rank(): int
    {
        return match ($this) {
            self::New => 0,
            self::Verified => 1,
            self::Trusted => 2,
            self::Top => 3,
            self::Recommended => 4,
        };
    }
}
