<?php

namespace App\Support;

use App\Enums\VendorTier;
use App\Models\Vendor;

/**
 * The way up the ranking: the next tier, and what a vendor still needs to
 * reach it. The website's points page and the Pro app both show it.
 */
class TierProgress
{
    /** The tier a vendor works towards next, or null at the top. */
    public static function next(VendorTier $tier): ?VendorTier
    {
        return match ($tier) {
            VendorTier::New, VendorTier::Verified => VendorTier::Trusted,
            VendorTier::Trusted => VendorTier::Top,
            VendorTier::Top => VendorTier::Recommended,
            VendorTier::Recommended => null,
        };
    }

    /**
     * What the vendor still needs for the tier above them.
     *
     * @return array<int, array{label: string, current: string, target: string, met: bool}>
     */
    public static function requirements(Vendor $vendor): array
    {
        $targets = match (self::next($vendor->tier)) {
            VendorTier::Trusted => ['completed' => 5, 'rating' => 4.0, 'reviews' => 3, 'response' => 0, 'completion' => 0],
            VendorTier::Top => ['completed' => 15, 'rating' => 4.5, 'reviews' => 8, 'response' => 90, 'completion' => 0],
            VendorTier::Recommended => ['completed' => 30, 'rating' => 4.7, 'reviews' => 15, 'response' => 95, 'completion' => 90],
            default => null,
        };

        if (! $targets) {
            return [];
        }

        $rows = [
            ['label' => __('props.vendor.booking_selesai'), 'value' => $vendor->completed_bookings_count, 'target' => $targets['completed'], 'suffix' => ''],
            ['label' => __('props.vendor.rating_purata'), 'value' => (float) $vendor->rating_avg, 'target' => $targets['rating'], 'suffix' => ''],
            ['label' => __('props.vendor.jumlah_review'), 'value' => $vendor->reviews_count, 'target' => $targets['reviews'], 'suffix' => ''],
            ['label' => __('props.vendor.response_rate_2'), 'value' => $vendor->response_rate ?? 0, 'target' => $targets['response'], 'suffix' => '%'],
            ['label' => __('props.vendor.completion_rate_2'), 'value' => $vendor->completion_rate, 'target' => $targets['completion'], 'suffix' => '%'],
        ];

        return collect($rows)
            ->reject(fn (array $row): bool => $row['target'] <= 0)
            ->map(fn (array $row): array => [
                'label' => $row['label'],
                'current' => $row['value'].$row['suffix'],
                'target' => $row['target'].$row['suffix'],
                'met' => $row['value'] >= $row['target'],
            ])
            ->values()
            ->all();
    }
}
