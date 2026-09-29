<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\DayStatus;
use App\Enums\DepositChannel;
use App\Enums\OnlineBookingState;
use App\Enums\VendorFeature;
use App\Models\Vendor;
use App\Models\VendorBookingSetting;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * The one place that decides whether a vendor can be booked on a date, and
 * whether they take online bookings at all. The public calendar, the booking
 * request, CreateBooking (under a lock), the vendor's own calendar and
 * Vendor::isAvailableOn() all ask here, so a rule changes in one place.
 *
 * A day's capacity is the vendor's max_per_day. Active bookings (pending or
 * confirmed) take a place each; a closed date takes the whole day, or
 * `slots` places when an outside booking took only some of them. Dates are
 * calendar days in Malaysian time (app.timezone).
 */
final class VendorAvailability
{
    private function __construct(
        private Vendor $vendor,
        private VendorBookingSetting $settings,
    ) {}

    public static function for(Vendor $vendor): self
    {
        return new self($vendor, $vendor->bookingSettingsOrDefault());
    }

    public function settings(): VendorBookingSetting
    {
        return $this->settings;
    }

    /**
     * Places taken on a day. A date closed outright counts as full whatever the
     * capacity.
     */
    public function takenOn(CarbonInterface|string $date): int
    {
        $day = Carbon::parse($date)->toDateString();

        return $this->taken($day, $day)[$day] ?? 0;
    }

    /**
     * Room for one more booking that day, ignoring the online-only rules
     * (weekdays, lead time). What a vendor recording their own booking needs.
     */
    public function hasCapacityOn(CarbonInterface|string $date): bool
    {
        return $this->takenOn($date) < $this->capacity();
    }

    /**
     * Everything a couple booking online is held to: not past, far enough
     * ahead, not too far, an open weekday, and a place left.
     */
    public function dayFor(CarbonInterface|string $date): DayStatus
    {
        $day = Carbon::parse($date)->startOfDay();

        return $this->statusFor($day, $this->takenOn($day));
    }

    /**
     * Every day in a range with its status and the places left, in two
     * queries whatever the length of the range.
     *
     * @return array<string, array{status: string, remaining: int}>
     */
    public function calendar(CarbonInterface $from, CarbonInterface $to): array
    {
        $taken = $this->taken($from->toDateString(), $to->toDateString());
        $days = [];

        for ($day = Carbon::parse($from)->startOfDay(); $day->lte($to); $day->addDay()) {
            $used = $taken[$day->toDateString()] ?? 0;
            $status = $this->statusFor($day, $used);

            $days[$day->toDateString()] = [
                'status' => $status->value,
                'remaining' => $status->isOpen() ? max(0, $this->capacity() - $used) : 0,
            ];
        }

        return $days;
    }

    /**
     * The next open days from today, for the page a visitor without
     * JavaScript gets.
     *
     * @return list<Carbon>
     */
    public function nextOpenDays(int $count, int $searchDays = 120): array
    {
        $from = today();
        $open = [];

        foreach ($this->calendar($from, $from->copy()->addDays($searchDays)) as $date => $day) {
            if ($day['status'] === DayStatus::Open->value) {
                $open[] = Carbon::parse($date);

                if (count($open) === $count) {
                    break;
                }
            }
        }

        return $open;
    }

    public function onlineState(): OnlineBookingState
    {
        return match (true) {
            ! app(OnlineBookingSettings::class)->isEnabled() => OnlineBookingState::GloballyOff,
            ! $this->vendor->isApproved() => OnlineBookingState::NotApproved,
            ! $this->vendor->hasFeature(VendorFeature::OnlineBooking) => OnlineBookingState::FeatureOff,
            ! $this->settings->enabled => OnlineBookingState::SwitchedOff,
            ! $this->vendor->packages()->where('is_active', true)->exists() => OnlineBookingState::NoPackages,
            $this->paymentChannel() === null => OnlineBookingState::NoPaymentPath,
            ! $this->settings->calendarIsFresh() => OnlineBookingState::CalendarStale,
            default => OnlineBookingState::Open,
        };
    }

    public function acceptsOnlineBookings(): bool
    {
        return $this->onlineState()->isOpen();
    }

    /**
     * How a couple would pay the deposit: the vendor's own Herepay account when
     * connected, a bank transfer to the vendor when they have given their bank
     * details and manual transfer is allowed, otherwise nothing.
     */
    public function paymentChannel(): ?DepositChannel
    {
        if ($this->settings->hasHerepay() && filled(config('services.herepay.base_url'))) {
            return DepositChannel::Herepay;
        }

        if ($this->settings->hasManualInstructions() && app(PaymentSettings::class)->manualTransferEnabled()) {
            return DepositChannel::Manual;
        }

        return null;
    }

    public function capacity(): int
    {
        return max(1, (int) $this->settings->max_per_day);
    }

    private function statusFor(Carbon $day, int $taken): DayStatus
    {
        $today = today();

        return match (true) {
            $day->lte($today) => DayStatus::Past,
            $day->lt($today->copy()->addDays(max(1, $this->settings->min_lead_days))) => DayStatus::TooSoon,
            $day->gt($today->copy()->addMonthsNoOverflow($this->settings->max_advance_months)) => DayStatus::TooFar,
            ! in_array($day->dayOfWeekIso, $this->settings->weekdays(), true) => DayStatus::WeekdayOff,
            $taken >= PHP_INT_MAX => DayStatus::Closed,
            $taken >= $this->capacity() => DayStatus::Full,
            default => DayStatus::Open,
        };
    }

    /**
     * Places taken per date in a range: active bookings plus closed dates.
     *
     * @return array<string, int>
     */
    private function taken(string $from, string $to): array
    {
        $taken = [];

        $closed = $this->vendor->unavailableDates()
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->get(['date', 'slots']);

        foreach ($closed as $row) {
            $date = $row->date->toDateString();

            if (($taken[$date] ?? 0) === PHP_INT_MAX) {
                continue;
            }

            $taken[$date] = $row->slots === null ? PHP_INT_MAX : ($taken[$date] ?? 0) + $row->slots;
        }

        $bookings = $this->vendor->bookings()
            ->whereIn('status', [BookingStatus::PendingPayment, BookingStatus::Confirmed])
            ->whereDate('event_date', '>=', $from)
            ->whereDate('event_date', '<=', $to)
            ->get(['event_date']);

        foreach ($bookings as $booking) {
            $date = $booking->event_date->toDateString();

            if (($taken[$date] ?? 0) !== PHP_INT_MAX) {
                $taken[$date] = ($taken[$date] ?? 0) + 1;
            }
        }

        return $taken;
    }
}
