<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\PointReason;
use App\Models\Payment;
use App\Notifications\BookingConfirmed;
use App\Notifications\PaymentReceived;
use Illuminate\Support\Facades\DB;

class RecordSuccessfulPayment
{
    public function __construct(private AwardVendorPoints $awardPoints) {}

    /**
     * Mark a payment as paid and confirm the booking when the deposit is settled.
     */
    public function handle(Payment $payment, string $gatewayReference): Payment
    {
        return DB::transaction(function () use ($payment, $gatewayReference): Payment {
            $payment->update([
                'status' => PaymentStatus::Paid,
                'gateway_reference' => $gatewayReference,
                'paid_at' => now(),
            ]);

            $booking = $payment->booking;

            $vendor = $booking->vendor;

            if ($payment->type === PaymentType::Deposit) {
                $this->awardPoints->award($vendor, PointReason::DepositPaid, $booking);
            }

            if ($booking->fresh()->isFullyPaid()) {
                $this->awardPoints->award($vendor, PointReason::FullPayment, $booking);
            }

            $booking->user->notify(new PaymentReceived($payment));

            if ($payment->type === PaymentType::Deposit && $booking->status === BookingStatus::PendingPayment) {
                $booking->update([
                    'status' => BookingStatus::Confirmed,
                    'confirmed_at' => now(),
                ]);

                $booking->user->notify(new BookingConfirmed($booking));
                $vendor->user->notify(new BookingConfirmed($booking));
            }

            return $payment;
        });
    }
}
