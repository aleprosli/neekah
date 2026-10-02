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
     * @return array<int, array{key: string, label: string, current: string, target: string, met: bool}>
     */
    public static function requirements(Vendor $vendor): array
    {
        $next = self::next($vendor->tier);

        return $next ? self::requirementsFor($vendor, $next) : [];
    }

    /**
     * Each requirement of a tier (VendorTier::requirements) against where the
     * vendor stands: a complete profile, reviews, their rating and, for the
     * top rung, a clean record.
     *
     * @return array<int, array{key: string, label: string, current: string, target: string, met: bool}>
     */
    public static function requirementsFor(Vendor $vendor, VendorTier $tier): array
    {
        $needs = $tier->requirements();

        if ($needs === null) {
            return [];
        }

        $reviews = $vendor->tierReviewStats();
        $complete = $vendor->hasCompleteSetup();
        $rows = [
            [
                'key' => 'profile',
                'label' => __('pages.ranking.req_profile'),
                'current' => $complete ? __('pages.ranking.done') : __('pages.ranking.not_yet'),
                'target' => __('pages.ranking.done'),
                'met' => $complete,
            ],
            [
                'key' => 'reviews',
                'label' => __('pages.ranking.req_reviews'),
                'current' => (string) $reviews['count'],
                'target' => (string) $needs['reviews'],
                'met' => $reviews['count'] >= $needs['reviews'],
            ],
            [
                'key' => 'rating',
                'label' => __('pages.ranking.req_rating'),
                'current' => number_format($reviews['rating'], 1),
                'target' => number_format($needs['rating'], 1),
                'met' => $reviews['count'] > 0 && $reviews['rating'] >= $needs['rating'],
            ],
        ];

        if ($needs['clean_record']) {
            $clean = ! $vendor->violations()->upheld()->where('resolved_at', '>=', now()->subMonths(6))->exists();
            $rows[] = [
                'key' => 'record',
                'label' => __('pages.ranking.req_record'),
                'current' => $clean ? __('pages.ranking.done') : __('pages.ranking.not_yet'),
                'target' => __('pages.ranking.done'),
                'met' => $clean,
            ];
        }

        return $rows;
    }
}
