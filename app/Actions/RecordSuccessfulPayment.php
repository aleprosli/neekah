<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class RecordSuccessfulPayment
{
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

            if ($payment->type === PaymentType::Deposit && $booking->status === BookingStatus::PendingPayment) {
                $booking->update([
                    'status' => BookingStatus::Confirmed,
                    'confirmed_at' => now(),
                ]);
            }

            return $payment;
        });
    }
}
