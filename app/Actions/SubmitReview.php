<?php

namespace App\Actions;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubmitReview
{
    public function __construct(private RecalculateVendorStats $recalculateStats) {}

    /**
     * Store a verified review for a completed booking and refresh the vendor's rating.
     *
     * @param  array{rating: int, quality: int, service: int, communication: int, value: int, punctuality: int, comment: string}  $attributes
     */
    public function handle(Booking $booking, array $attributes): Review
    {
        if (! $booking->canBeReviewed()) {
            throw new InvalidArgumentException('Review hanya boleh diberi sekali selepas booking selesai.');
        }

        return DB::transaction(function () use ($booking, $attributes): Review {
            $review = Review::create([
                ...$attributes,
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'vendor_id' => $booking->vendor_id,
            ]);

            $this->recalculateStats->handle($booking->vendor);

            return $review;
        });
    }
}
