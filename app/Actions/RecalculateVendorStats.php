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
        $vendor->fill([
            'rating_avg' => round((float) $vendor->reviews()->avg('rating'), 2),
            'reviews_count' => $vendor->reviews()->count(),
            'completed_bookings_count' => $vendor->bookings()->where('status', BookingStatus::Completed)->count(),
        ]);

        $vendor->completion_rate = $vendor->calculateCompletionRate();
        $vendor->response_rate = $vendor->calculateResponseRate();
        $vendor->save();

        $this->awardPoints->syncMilestones($vendor);
        $vendor->refresh();

        if (! $vendor->tier_locked) {
            $vendor->tier = $this->tierFor($vendor);
        }

        $vendor->score = $vendor->calculateScore();
        $vendor->save();

        return $vendor;
    }

    /**
     * The ranking ladder from the kertas kerja. Recommended additionally requires a
     * clean violation record, so one serious breach drops a vendor out of it.
     */
    public function tierFor(Vendor $vendor): VendorTier
    {
        if ($vendor->status !== VendorStatus::Approved) {
            return VendorTier::New;
        }

        $rating = (float) $vendor->rating_avg;
        $completed = $vendor->completed_bookings_count;
        $recentViolations = $vendor->violations()->upheld()->where('resolved_at', '>=', now()->subMonths(6))->count();

        $earned = match (true) {
            $completed >= 30
                && $rating >= 4.7
                && $vendor->reviews_count >= 15
                && ($vendor->response_rate ?? 0) >= 95
                && $vendor->completion_rate >= 90
                && $recentViolations === 0 => VendorTier::Recommended,

            $completed >= 15
                && $rating >= 4.5
                && $vendor->reviews_count >= 8
                && ($vendor->response_rate ?? 0) >= 90 => VendorTier::Top,

            $completed >= 5
                && $rating >= 4.0
                && $vendor->reviews_count >= 3 => VendorTier::Trusted,

            default => VendorTier::Verified,
        };

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
