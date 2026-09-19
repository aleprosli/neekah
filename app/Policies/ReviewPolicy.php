<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Taking a review down, or putting it back. Admins only.
     *
     * A vendor who could remove a review would be choosing their own rating,
     * and the kertas kerja makes that rating 30% of the Recommended Vendor
     * score. What a vendor gets instead is reply() and report().
     */
    public function moderate(User $user, Review $review): bool
    {
        return $user->isAdmin();
    }

    /** The vendor's public answer, shown under the review on their profile. */
    public function reply(User $user, Review $review): bool
    {
        return $user->isVendor()
            && $user->vendor?->id === $review->vendor_id
            && ! $review->isHidden();
    }

    /** How a vendor objects to a review, since they cannot remove it. */
    public function report(User $user, Review $review): bool
    {
        return $this->reply($user, $review) && ! $review->isReported();
    }
}
