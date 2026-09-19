<?php

namespace App\Actions;

use App\Enums\PointReason;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Taking a review off a vendor's page, and putting it back.
 *
 * Only an admin reaches this. A vendor may answer a review or report it, but
 * a vendor who could remove one would be choosing their own rating, and the
 * kertas kerja makes that rating 30% of the Recommended Vendor score.
 *
 * Hiding keeps the row: who wrote it, what it said and why it was taken down
 * are all things the next admin needs to be able to read.
 */
class ModerateReview
{
    public function __construct(
        private AwardVendorPoints $points,
        private RecalculateVendorStats $stats,
        private StoreOptimizedImage $images,
    ) {}

    public function hide(Review $review, User $admin, string $reason): Review
    {
        $review->forceFill([
            'hidden_at' => now(),
            'hidden_by' => $admin->id,
            'hidden_reason' => $reason,
        ])->save();

        return $this->resettleVendor($review);
    }

    public function restore(Review $review): Review
    {
        $review->forceFill([
            'hidden_at' => null,
            'hidden_by' => null,
            'hidden_reason' => null,
        ])->save();

        return $this->resettleVendor($review);
    }

    /**
     * Gone for good, images included. Used for the ones that should never have
     * existed — spam, or a name the author asked to have erased.
     */
    public function delete(Review $review): void
    {
        DB::transaction(function () use ($review): void {
            $vendor = $review->vendor;

            foreach ($review->photos as $photo) {
                $this->images->delete($photo->path);
            }

            $this->points->revokeFor($vendor, $review);
            $review->delete();
            $this->stats->handle($vendor->refresh());
        });
    }

    /**
     * A booking review moving in or out of sight changes the rating, the
     * points it earned and the tier built on them. An open review changes
     * none of those, so nothing has to be recomputed for it.
     */
    private function resettleVendor(Review $review): Review
    {
        if (! $review->isVerified()) {
            return $review;
        }

        $vendor = $review->vendor;

        if ($review->isHidden()) {
            $this->points->revokeFor($vendor, $review);
        } elseif ($review->rating >= 4) {
            $this->points->award($vendor, PointReason::PositiveReview, $review);
        }

        $this->stats->handle($vendor->refresh());

        return $review;
    }
}
