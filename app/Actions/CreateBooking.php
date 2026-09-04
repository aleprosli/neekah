<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

class CreateBooking
{
    /**
     * Create a pending booking for a package and its deposit payment record.
     *
     * @param  array{event_date: \DateTimeInterface|string, wedding_id?: int|null, notes?: string|null}  $attributes
     */
    public function handle(User $customer, Vendor $vendor, Package $package, array $attributes): Booking
    {
        return DB::transaction(function () use ($customer, $vendor, $package, $attributes): Booking {
            $total = (float) $package->price;
            $deposit = round($total * Booking::DEPOSIT_RATE, 2);

            $booking = Booking::create([
                'reference' => Booking::generateReference(),
                'user_id' => $customer->id,
                'vendor_id' => $vendor->id,
                'wedding_id' => $attributes['wedding_id'] ?? null,
                'package_id' => $package->id,
                'package_name' => $package->name,
                'event_date' => $attributes['event_date'],
                'total_amount' => $total,
                'deposit_amount' => $deposit,
                'commission_rate' => Booking::COMMISSION_RATE,
                'commission_amount' => round($total * Booking::COMMISSION_RATE / 100, 2),
                'status' => BookingStatus::PendingPayment,
                'notes' => $attributes['notes'] ?? null,
            ]);

            Payment::create([
                'reference' => Payment::generateReference(),
                'booking_id' => $booking->id,
                'type' => PaymentType::Deposit,
                'amount' => $deposit,
                'status' => PaymentStatus::Pending,
                'gateway' => 'sandbox',
            ]);

            Payment::create([
                'reference' => Payment::generateReference(),
                'booking_id' => $booking->id,
                'type' => PaymentType::Balance,
                'amount' => $booking->balanceAmount(),
                'status' => PaymentStatus::Pending,
                'gateway' => 'sandbox',
            ]);

            return $booking;
        });
    }
}
