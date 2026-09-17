<?php

namespace App\Actions;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentRecorded;
use Illuminate\Support\Facades\DB;

class RecordManualPayment
{
    /**
     * Note a payment the couple says they have made.
     *
     * It changes nothing about the booking on its own: the vendor is the one
     * who can see their own bank account, so a record waits for them to confirm
     * it. An unchecked receipt must never be able to confirm a booking.
     *
     * @param  array{amount: float, paid_on: string, note?: string|null, receipt_image?: string|null}  $attributes
     */
    public function handle(Booking $booking, User $recorder, array $attributes): Payment
    {
        return DB::transaction(function () use ($booking, $recorder, $attributes): Payment {
            $payment = Payment::create([
                'reference' => Payment::generateReference(),
                'booking_id' => $booking->id,
                'recorded_by' => $recorder->id,
                'amount' => $attributes['amount'],
                'paid_on' => $attributes['paid_on'],
                'note' => $attributes['note'] ?? null,
                'receipt_image' => $attributes['receipt_image'] ?? null,
                'method' => PaymentMethod::ManualTransfer->value,
                'gateway' => 'manual',
                'status' => PaymentStatus::AwaitingVerification,
            ]);

            $booking->vendor->user->notify(new PaymentRecorded($payment));

            return $payment;
        });
    }
}
