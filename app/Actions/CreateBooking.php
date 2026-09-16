<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\PointReason;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\BookingCreatedForCustomer;
use App\Notifications\BookingCreatedForVendor;
use Illuminate\Support\Facades\DB;

class CreateBooking
{
    public function __construct(private AwardVendorPoints $awardPoints) {}

    /**
     * Create a pending booking for a package.
     *
     * No payment rows are created with it. The couple pays the vendor directly
     * and records what they actually paid, so inventing a deposit and a balance
     * here would only put two amounts on the page that nobody agreed to.
     *
     * @param  array{event_date: \DateTimeInterface|string, wedding_id?: int|null, notes?: string|null}  $attributes
     */
    public function handle(User $customer, Vendor $vendor, Package $package, array $attributes): Booking
    {
        return DB::transaction(function () use ($customer, $vendor, $package, $attributes): Booking {
            $total = (float) $package->price;

            $booking = Booking::create([
                'reference' => Booking::generateReference(),
                'user_id' => $customer->id,
                'vendor_id' => $vendor->id,
                'wedding_id' => $attributes['wedding_id'] ?? null,
                'package_id' => $package->id,
                'package_name' => $package->name,
                'event_date' => $attributes['event_date'],
                'total_amount' => $total,
                'commission_rate' => Booking::COMMISSION_RATE,
                'commission_amount' => round($total * Booking::COMMISSION_RATE / 100, 2),
                'status' => BookingStatus::PendingPayment,
                'notes' => $attributes['notes'] ?? null,
            ]);

            $booking->setRelation('vendor', $vendor);
            $booking->setRelation('user', $customer);

            $this->awardPoints->award($vendor, PointReason::PlatformBooking, $booking);

            $customer->notify(new BookingCreatedForCustomer($booking));
            $vendor->user->notify(new BookingCreatedForVendor($booking));

            return $booking;
        });
    }
}
