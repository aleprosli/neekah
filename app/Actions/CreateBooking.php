<?php

namespace App\Actions;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\DepositChannel;
use App\Enums\PointReason;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\BookingCreatedForCustomer;
use App\Notifications\BookingCreatedForVendor;
use App\Notifications\BookingHeldForCustomer;
use App\Support\OnlineBookingSettings;
use App\Support\VendorAvailability;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateBooking
{
    public function __construct(private AwardVendorPoints $awardPoints) {}

    /**
     * Create a pending booking for a package.
     *
     * The vendor's row is locked first and the date checked again under the
     * lock, so two couples submitting the last place on a day at the same
     * moment cannot both get it. The form request's check is only the friendly
     * early answer.
     *
     * A booking the vendor records carries no deposit: they dealt with the
     * customer themselves. An online booking stamps the vendor's own deposit
     * rule and holds the date until it is paid; its points are awarded when
     * the deposit is confirmed, so a hold that lapses earns nothing.
     *
     * @param  array{event_date: \DateTimeInterface|string, wedding_id?: int|null, notes?: string|null}  $attributes
     */
    public function handle(User $customer, Vendor $vendor, Package $package, array $attributes, bool $online = false): Booking
    {
        return DB::transaction(function () use ($customer, $vendor, $package, $attributes, $online): Booking {
            $locked = Vendor::query()->lockForUpdate()->findOrFail($vendor->id);
            $availability = VendorAvailability::for($locked);

            $this->ensureBookable($availability, $attributes['event_date'], $online);

            $total = (float) $package->price;
            $channel = $online ? $availability->paymentChannel() : null;

            $booking = Booking::create([
                'reference' => Booking::generateReference(),
                'user_id' => $customer->id,
                'vendor_id' => $vendor->id,
                'wedding_id' => $attributes['wedding_id'] ?? null,
                'package_id' => $package->id,
                'package_name' => $package->name,
                'event_date' => $attributes['event_date'],
                'total_amount' => $total,
                'deposit_amount' => $online ? $availability->settings()->depositFor($total) : null,
                'commission_rate' => Booking::COMMISSION_RATE,
                'commission_amount' => round($total * Booking::COMMISSION_RATE / 100, 2),
                'status' => BookingStatus::PendingPayment,
                'source' => $online ? BookingSource::Online : BookingSource::Vendor,
                'payment_mode' => $channel,
                'hold_expires_at' => $online ? now()->addHours(app(OnlineBookingSettings::class)->holdHours()) : null,
                'notes' => $attributes['notes'] ?? null,
            ]);

            $booking->setRelation('vendor', $vendor);
            $booking->setRelation('user', $customer);

            if ($online) {
                $customer->notify(new BookingHeldForCustomer($booking));

                // A transfer is on its way and only the vendor can confirm it.
                // A Herepay deposit tells them itself once it is paid.
                if ($channel === DepositChannel::Manual) {
                    $vendor->user->notify(new BookingCreatedForVendor($booking));
                }

                return $booking;
            }

            $this->awardPoints->award($vendor, PointReason::PlatformBooking, $booking);

            $customer->notify(new BookingCreatedForCustomer($booking));
            $vendor->user->notify(new BookingCreatedForVendor($booking));

            return $booking;
        });
    }

    private function ensureBookable(VendorAvailability $availability, \DateTimeInterface|string $date, bool $online): void
    {
        if (! $online) {
            if (! $availability->hasCapacityOn($date)) {
                throw ValidationException::withMessages(['event_date' => __('validation.custom.vendor_unavailable')]);
            }

            return;
        }

        if (! $availability->acceptsOnlineBookings()) {
            throw ValidationException::withMessages(['event_date' => __('validation.custom.online_booking_closed')]);
        }

        $day = $availability->dayFor($date);

        if (! $day->isOpen()) {
            throw ValidationException::withMessages(['event_date' => $day->refusal()]);
        }
    }
}
