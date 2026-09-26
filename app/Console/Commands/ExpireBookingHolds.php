<?php

namespace App\Console\Commands;

use App\Actions\CancelBooking;
use App\Actions\StartDepositPayment;
use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\CancellationReason;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use Illuminate\Console\Command;

class ExpireBookingHolds extends Command
{
    protected $signature = 'neekah:expire-booking-holds';

    protected $description = 'Release the dates of online bookings whose deposit was not paid in time';

    /**
     * Minutes past the hold before a booking is released, so a Herepay
     * callback arriving a little late still finds its booking.
     */
    public const GRACE_MINUTES = 15;

    /**
     * An online booking holds its date for its deposit. Unpaid when the hold
     * runs out, it is cancelled and the date opens for someone else. One with
     * a receipt waiting for the vendor is left alone: the couple did pay.
     */
    public function handle(CancelBooking $cancel): int
    {
        $released = 0;

        Booking::query()
            ->where('source', BookingSource::Online)
            ->where('status', BookingStatus::PendingPayment)
            ->where('hold_expires_at', '<', now()->subMinutes(self::GRACE_MINUTES))
            ->whereDoesntHave('payments', fn ($query) => $query->whereIn('status', [PaymentStatus::Paid, PaymentStatus::AwaitingVerification]))
            ->with(['vendor.user', 'user'])
            ->chunkById(100, function ($bookings) use ($cancel, &$released): void {
                foreach ($bookings as $booking) {
                    $booking->payments()
                        ->where('gateway', StartDepositPayment::GATEWAY)
                        ->where('status', PaymentStatus::Pending)
                        ->update(['status' => PaymentStatus::Failed]);

                    $cancel->handle($booking, null, null, CancellationReason::Expired);
                    $released++;
                }
            });

        $this->components->info("{$released} booking hold(s) released.");

        return self::SUCCESS;
    }
}
