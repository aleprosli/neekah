<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\PointReason;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Vendor;
use App\Notifications\BookingConfirmed;
use App\Notifications\DepositNeedsRefund;
use App\Notifications\PaymentReceived;
use App\Support\VendorAvailability;
use Illuminate\Support\Facades\DB;

/**
 * A deposit paid on the vendor's Herepay account: the gateway counterpart of
 * VerifyManualPayment, and the only thing that confirms an online booking from a
 * gateway. SettlePayment makes sure it runs once, however often Herepay calls.
 *
 * A payment can land after the hold ran out and the booking was released. If
 * the date is still free the booking is put back; if someone else has taken
 * it, the money is recorded and both sides are told the vendor owes a refund.
 */
class ConfirmOnlineDeposit
{
    public function __construct(private AwardVendorPoints $awardPoints) {}

    /**
     * Called by SettlePayment once, inside its lock, with the payment already
     * marked paid.
     */
    public function fulfil(Payment $payment): void
    {
        $payment->update(['paid_on' => today()]);
        $booking = Booking::query()->lockForUpdate()->findOrFail($payment->booking_id);
        $vendor = Vendor::query()->lockForUpdate()->findOrFail($booking->vendor_id);
        $outcome = $this->confirm($booking, $vendor);

        DB::afterCommit(function () use ($payment, $outcome): void {
            $payment->refresh();
            $booking = $payment->booking;

            match ($outcome) {
                'confirmed' => [
                    $booking->user->notify(new PaymentReceived($payment)),
                    $booking->user->notify(new BookingConfirmed($booking)),
                    $booking->vendor->user->notify(new BookingConfirmed($booking)),
                ],
                'paid' => $booking->user->notify(new PaymentReceived($payment)),
                'refund' => [
                    $booking->user->notify(new DepositNeedsRefund($payment)),
                    $booking->vendor->user->notify(new DepositNeedsRefund($payment)),
                    report(new \RuntimeException("Deposit {$payment->reference} paid after booking {$booking->reference} lost its date; vendor must refund.")),
                ],
            };
        });
    }

    /**
     * @return 'confirmed'|'paid'|'refund'
     */
    private function confirm(Booking $booking, Vendor $vendor): string
    {
        if ($booking->status === BookingStatus::Cancelled) {
            if (! VendorAvailability::for($vendor)->hasCapacityOn($booking->event_date)) {
                return 'refund';
            }

            $booking->update(['status' => BookingStatus::PendingPayment, 'cancelled_at' => null, 'cancelled_reason' => null]);
        }

        if ($booking->status !== BookingStatus::PendingPayment) {
            return 'paid';
        }

        $booking->update(['status' => BookingStatus::Confirmed, 'confirmed_at' => now(), 'hold_expires_at' => null]);
        $this->awardPoints->award($vendor, PointReason::PlatformBooking, $booking);
        $this->awardPoints->award($vendor, PointReason::DepositPaid, $booking);

        if ($booking->isFullyPaid()) {
            $this->awardPoints->award($vendor, PointReason::FullPayment, $booking);
        }

        return 'confirmed';
    }
}
