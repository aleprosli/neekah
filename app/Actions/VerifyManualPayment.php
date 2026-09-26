<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PointReason;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\BookingConfirmed;
use App\Notifications\PaymentReceived;
use App\Notifications\PaymentRejected;
use App\Support\OnlineBookingSettings;
use Illuminate\Support\Facades\DB;

class VerifyManualPayment
{
    public function __construct(private AwardVendorPoints $awardPoints) {}

    /**
     * The vendor has found the money in their account.
     *
     * The first verified payment confirms the booking: that is the moment both
     * sides agree something real has changed hands.
     */
    public function handle(Payment $payment, User $verifier): Payment
    {
        return DB::transaction(function () use ($payment, $verifier): Payment {
            $payment->update([
                'status' => PaymentStatus::Paid,
                'paid_at' => $payment->paid_on ?? now(),
                'verified_at' => now(),
                'verified_by' => $verifier->id,
            ]);

            $booking = $payment->booking->fresh();
            $vendor = $booking->vendor;

            // An online booking earns its booking points once the deposit is
            // real, not when it was only held (CreateBooking).
            if ($booking->isOnline()) {
                $this->awardPoints->award($vendor, PointReason::PlatformBooking, $booking);
            }

            $this->awardPoints->award($vendor, PointReason::DepositPaid, $booking);

            if ($booking->isFullyPaid()) {
                $this->awardPoints->award($vendor, PointReason::FullPayment, $booking);
            }

            $booking->user->notify(new PaymentReceived($payment));

            if ($booking->status === BookingStatus::PendingPayment) {
                $booking->update(['status' => BookingStatus::Confirmed, 'confirmed_at' => now(), 'hold_expires_at' => null]);

                $booking->user->notify(new BookingConfirmed($booking));
                $vendor->user->notify(new BookingConfirmed($booking));
            }

            return $payment;
        });
    }

    /**
     * The vendor cannot find the money: the record is marked as not received.
     * An online booking still waiting for its deposit gets a fresh hold, so the
     * couple has time to send the right receipt before the date is released.
     */
    public function reject(Payment $payment, User $verifier): Payment
    {
        $payment->update([
            'status' => PaymentStatus::Failed,
            'verified_at' => now(),
            'verified_by' => $verifier->id,
        ]);

        $booking = $payment->booking;

        if ($booking->isOnline() && $booking->status === BookingStatus::PendingPayment) {
            $booking->update(['hold_expires_at' => now()->addHours(app(OnlineBookingSettings::class)->holdHours())]);
        }

        $payment->booking->user->notify(new PaymentRejected($payment));

        return $payment;
    }
}
