<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\PointReason;
use App\Models\Booking;
use App\Notifications\BookingCompleted;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CompleteBooking
{
    public function __construct(
        private RecalculateVendorStats $recalculateStats,
        private AwardVendorPoints $awardPoints,
    ) {}

    /**
     * Mark a confirmed booking as completed after the event and refresh vendor stats.
     */
    public function handle(Booking $booking): Booking
    {
        if ($booking->status !== BookingStatus::Confirmed) {
            throw new InvalidArgumentException('Hanya booking yang telah disahkan boleh ditandakan selesai.');
        }

        return DB::transaction(function () use ($booking): Booking {
            $booking->update([
                'status' => BookingStatus::Completed,
                'completed_at' => now(),
            ]);

            $this->awardPoints->award($booking->vendor, PointReason::BookingCompleted, $booking);
            $this->recalculateStats->handle($booking->vendor);

            $booking->user->notify(new BookingCompleted($booking));

            return $booking;
        });
    }
}
