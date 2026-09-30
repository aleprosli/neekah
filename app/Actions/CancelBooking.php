<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\CancellationReason;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingCancelled;
use Illuminate\Support\Facades\DB;

class CancelBooking
{
    public function __construct(private AwardVendorPoints $awardPoints, private RecalculateVendorStats $recalculate) {}

    /**
     * Call off a booking: by the couple before anything is paid, by the vendor
     * (who then settles any refund with the couple directly), or by Neekah
     * itself when an online booking's deposit hold runs out ($canceller null).
     *
     * The points the vendor earned for it go back too: a booking made by
     * mistake and undone the same evening never happened, and leaving its 100
     * points behind would make the leaderboard reward the mistake.
     */
    public function handle(Booking $booking, ?User $canceller, ?string $reason = null, CancellationReason $why = CancellationReason::Couple): Booking
    {
        return DB::transaction(function () use ($booking, $canceller, $reason, $why): Booking {
            $booking->update([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_reason' => $why,
                'hold_expires_at' => null,
                'notes' => $reason ? trim($booking->notes."\n\n".__('notifications.booking_cancelled.note_prefix').' '.$reason) : $booking->notes,
            ]);

            $vendor = $booking->vendor;

            $this->awardPoints->revokeFor($vendor, $booking);
            $this->recalculate->handle($vendor);

            $booking->user->notify(new BookingCancelled($booking, $canceller, $reason, $why));
            $vendor->user->notify(new BookingCancelled($booking, $canceller, $reason, $why));

            return $booking;
        });
    }
}
