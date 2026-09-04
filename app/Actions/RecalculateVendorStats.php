<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Models\Vendor;

class RecalculateVendorStats
{
    /**
     * Refresh the denormalised rating, review, completion counters and the ranking score.
     */
    public function handle(Vendor $vendor): Vendor
    {
        $vendor->fill([
            'rating_avg' => round((float) $vendor->reviews()->avg('rating'), 2),
            'reviews_count' => $vendor->reviews()->count(),
            'completed_bookings_count' => $vendor->bookings()->where('status', BookingStatus::Completed)->count(),
        ]);

        $vendor->score = $vendor->calculateScore();
        $vendor->save();

        return $vendor;
    }
}
