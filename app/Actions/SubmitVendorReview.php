<?php

namespace App\Actions;

use App\Models\Review;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * A review written straight on a vendor's profile, by anyone.
 *
 * These carry no booking, so they are deliberately kept out of rating_avg
 * and the vendor's points — see Vendor::rankingReviews(). Since 2 Oct 2026
 * the ranking tier does count them (Vendor::tierReviews, never the ones a
 * vendor adds themselves), so the vendor is recalculated after each one.
 * They are published the moment they are written; an admin is what takes one
 * down again.
 */
class SubmitVendorReview
{
    public function __construct(private StoreOptimizedImage $images, private RecalculateVendorStats $stats) {}

    /**
     * @param  array{rating: int, comment: string, author_name?: string|null, author_email?: string|null}  $attributes
     * @param  array<int, UploadedFile>  $photos
     * @param  User|null  $author  The signed-in writer, if there is one.
     * @param  User|null  $addedBy  The admin typing in a review someone else wrote.
     */
    public function handle(
        Vendor $vendor,
        array $attributes,
        array $photos = [],
        ?User $author = null,
        ?User $addedBy = null,
    ): Review {
        $review = DB::transaction(function () use ($vendor, $attributes, $photos, $author, $addedBy): Review {
            $review = Review::create([
                ...$attributes,
                'vendor_id' => $vendor->id,
                'booking_id' => null,
                'user_id' => $author?->id,
                // An account signs its own name; only an anonymous writer, or
                // an admin entering someone else's words, supplies one.
                'author_name' => $author?->name ?? ($attributes['author_name'] ?? null),
                'added_by' => $addedBy?->id,
            ]);

            $this->attachPhotos($review, $photos);

            return $review;
        });

        // A review the vendor typed in themselves never counts; anyone else's may move the tier, and only the tier.
        if ($addedBy === null || $addedBy->id !== $vendor->user_id) {
            $this->stats->refreshTier($vendor);
        }

        return $review;
    }

    /**
     * @param  array<int, UploadedFile>  $photos
     */
    public function attachPhotos(Review $review, array $photos): void
    {
        foreach (array_slice(array_values($photos), 0, Review::MAX_PHOTOS) as $at => $photo) {
            $review->photos()->create([
                'path' => $this->images->handle($photo, 'reviews'),
                'sort_order' => $at,
            ]);
        }
    }
}
