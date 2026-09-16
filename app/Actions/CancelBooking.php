<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingCancelled;
use Illuminate\Support\Facades\DB;

class CancelBooking
{
    public function __construct(private AwardVendorPoints $awardPoints, private RecalculateVendorStats $recalculate) {}

    /**
     * Call off a booking nobody has been paid for.
     *
     * The points the vendor earned for it go back too: a booking made by
     * mistake and undone the same evening never happened, and leaving its 100
     * points behind would make the leaderboard reward the mistake.
     */
    public function handle(Booking $booking, User $canceller, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($booking, $canceller, $reason): Booking {
            $booking->update([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
                'notes' => $reason ? trim($booking->notes."\n\nDibatalkan: ".$reason) : $booking->notes,
            ]);

            $vendor = $booking->vendor;

            $this->awardPoints->revokeFor($vendor, $booking);
            $this->recalculate->handle($vendor);

            $booking->user->notify(new BookingCancelled($booking, $canceller, $reason));
            $vendor->user->notify(new BookingCancelled($booking, $canceller, $reason));

            return $booking;
        });
    }
}
