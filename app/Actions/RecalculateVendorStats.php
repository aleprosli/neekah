<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\VendorStatus;
use App\Enums\VendorTier;
use App\Models\Vendor;

class RecalculateVendorStats
{
    public function __construct(private AwardVendorPoints $awardPoints) {}

    /**
     * Refresh the denormalised counters, the point milestones, the Vendor Score
     * and the ranking tier. Admins can pin a tier by setting tier_locked.
     */
    public function handle(Vendor $vendor): Vendor
    {
        // A score or a counter is not a change to the vendor's page. Letting
        // these writes bump updated_at made the sitemap's lastmod move every
        // time a vendor opened their own points page, which teaches a crawler
        // that our lastmod means nothing.
        $vendor->timestamps = false;

        $vendor->fill([
            'rating_avg' => round((float) $vendor->rankingReviews()->avg('rating'), 2),
            'reviews_count' => $vendor->rankingReviews()->count(),
            'completed_bookings_count' => $vendor->bookings()->where('status', BookingStatus::Completed)->count(),
        ]);

        $vendor->completion_rate = $vendor->calculateCompletionRate();
        $vendor->response_rate = $vendor->calculateResponseRate();
        $vendor->save();

        $this->awardPoints->syncMilestones($vendor);
        $vendor->refresh();
        $vendor->timestamps = false;

        if (! $vendor->tier_locked) {
            $vendor->tier = $this->tierFor($vendor);
        }

        $vendor->score = $vendor->calculateScore();
        $vendor->save();

        $vendor->timestamps = true;

        return $vendor;
    }

    /**
     * Only the tier, for an open review coming or going: it counts towards
     * the ladder but earns no points and moves no rating or score, so the
     * rest of handle() has nothing to do.
     */
    public function refreshTier(Vendor $vendor): Vendor
    {
        if ($vendor->tier_locked) {
            return $vendor;
        }

        $tier = $this->tierFor($vendor);

        if ($tier !== $vendor->tier) {
            $vendor->timestamps = false;
            $vendor->tier = $tier;
            $vendor->save();
            $vendor->timestamps = true;
        }

        return $vendor;
    }

    /**
     * The ranking ladder, written down in VendorTier::requirements(): a
     * complete profile, reviews and their rating, and for the top rung a clean
     * violation record, so one serious breach drops a vendor out of it.
     */
    public function tierFor(Vendor $vendor): VendorTier
    {
        if ($vendor->status !== VendorStatus::Approved) {
            return VendorTier::New;
        }

        $reviews = $vendor->tierReviewStats();
        $complete = $vendor->hasCompleteSetup();
        $recentViolations = $vendor->violations()->upheld()->where('resolved_at', '>=', now()->subMonths(6))->count();

        // The highest tier whose every requirement is met (VendorTier::requirements).
        $earned = collect([VendorTier::Recommended, VendorTier::Top, VendorTier::Trusted])
            ->first(function (VendorTier $tier) use ($reviews, $complete, $recentViolations): bool {
                $needs = $tier->requirements();

                return (! $needs['complete_profile'] || $complete)
                    && $reviews['count'] >= $needs['reviews']
                    && $reviews['rating'] >= $needs['rating']
                    && (! $needs['clean_record'] || $recentViolations === 0);
            }) ?? VendorTier::Verified;

        return $this->capForViolations($earned, $recentViolations);
    }

    /**
     * Each upheld violation in the last six months costs the vendor a rung, which is
     * the "ranking reduction" the kertas kerja pairs with the point deduction.
     */
    private function capForViolations(VendorTier $earned, int $recentViolations): VendorTier
    {
        $ceiling = match (true) {
            $recentViolations === 0 => VendorTier::Recommended,
            $recentViolations === 1 => VendorTier::Top,
            $recentViolations === 2 => VendorTier::Trusted,
            default => VendorTier::Verified,
        };

        return $earned->rank() <= $ceiling->rank() ? $earned : $ceiling;
    }
}
