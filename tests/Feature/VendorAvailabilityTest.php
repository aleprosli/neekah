<?php

use App\Enums\BookingStatus;
use App\Enums\DayStatus;
use App\Enums\DepositType;
use App\Enums\OnlineBookingState;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Package;
use App\Models\Vendor;
use App\Support\VendorAvailability;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    enableOnlineBooking();
    $this->travelTo(Carbon::parse('2026-10-05 10:00')); // a Monday
    $this->vendor = Vendor::factory()->for(Category::first())->takingOnlineBookings()->create();
    Package::factory()->for($this->vendor)->create(['price' => 3000]);
});

function day(Vendor $vendor, string $date): DayStatus
{
    return VendorAvailability::for($vendor->fresh())->dayFor($date);
}

it('works out a deposit as a share of the price or a fixed sum, never below RM1 or above the price', function () {
    expect(DepositType::Percent->amountFor(3000, 30))->toBe(900.0)
        ->and(DepositType::Fixed->amountFor(3000, 500))->toBe(500.0)
        ->and(DepositType::Fixed->amountFor(300, 500))->toBe(300.0)
        ->and(DepositType::Percent->amountFor(2, 10))->toBe(1.0);
});

it('opens only the weekdays the vendor picked', function () {
    $this->vendor->bookingSettings->update(['available_weekdays' => [6, 7]]);

    expect(day($this->vendor, '2026-10-17'))->toBe(DayStatus::Open) // Saturday
        ->and(day($this->vendor, '2026-10-15'))->toBe(DayStatus::WeekdayOff); // Thursday
});

it('refuses today, dates inside the lead time and dates beyond the window', function () {
    $this->vendor->bookingSettings->update(['min_lead_days' => 14, 'max_advance_months' => 6]);

    expect(day($this->vendor, '2026-10-05'))->toBe(DayStatus::Past)
        ->and(day($this->vendor, '2026-10-10'))->toBe(DayStatus::TooSoon)
        ->and(day($this->vendor, '2026-10-19'))->toBe(DayStatus::Open)
        ->and(day($this->vendor, '2027-05-01'))->toBe(DayStatus::TooFar);
});

it('fills a day by its capacity, counting bookings and outside places', function () {
    $this->vendor->bookingSettings->update(['max_per_day' => 2]);
    Booking::factory()->for($this->vendor)->create(['event_date' => '2026-11-07', 'status' => BookingStatus::Confirmed]);

    expect(day($this->vendor, '2026-11-07'))->toBe(DayStatus::Open);

    $this->vendor->unavailableDates()->create(['date' => '2026-11-07', 'slots' => 1]);
    expect(day($this->vendor, '2026-11-07'))->toBe(DayStatus::Full);

    $this->vendor->unavailableDates()->create(['date' => '2026-11-08']);
    expect(day($this->vendor, '2026-11-08'))->toBe(DayStatus::Closed);
});

it('frees a date when its booking is cancelled', function () {
    $booking = Booking::factory()->for($this->vendor)->create(['event_date' => '2026-11-07']);
    expect(day($this->vendor, '2026-11-07'))->toBe(DayStatus::Full);

    $booking->update(['status' => BookingStatus::Cancelled]);
    expect(day($this->vendor, '2026-11-07'))->toBe(DayStatus::Open);
});

it('builds a whole month in two queries', function () {
    $availability = VendorAvailability::for($this->vendor->fresh()->load('bookingSettings'));

    DB::enableQueryLog();
    $days = $availability->calendar(Carbon::parse('2026-11-01'), Carbon::parse('2026-11-30'));

    expect($days)->toHaveCount(30)
        ->and(DB::getQueryLog())->toHaveCount(2);
});

it('counts dates in Malaysian time around midnight', function () {
    $this->travelTo(Carbon::parse('2026-10-05 23:30', 'Asia/Kuala_Lumpur'));
    expect(day($this->vendor, '2026-10-06'))->toBe(DayStatus::Open);

    $this->travelTo(Carbon::parse('2026-10-06 00:30', 'Asia/Kuala_Lumpur'));
    expect(day($this->vendor, '2026-10-06'))->toBe(DayStatus::Past);
});

it('says why a vendor is not taking online bookings', function () {
    expect(VendorAvailability::for($this->vendor->fresh())->onlineState())->toBe(OnlineBookingState::Open);

    $this->vendor->bookingSettings->update(['calendar_confirmed_at' => now()->subDays(20)]);
    expect(VendorAvailability::for($this->vendor->fresh())->onlineState())->toBe(OnlineBookingState::CalendarStale);

    $this->vendor->bookingSettings->update(['calendar_confirmed_at' => now(), 'manual_instructions' => null]);
    expect(VendorAvailability::for($this->vendor->fresh())->onlineState())->toBe(OnlineBookingState::NoPaymentPath);

    $this->vendor->update(['pro_until' => null]);
    expect(VendorAvailability::for($this->vendor->fresh())->onlineState())->toBe(OnlineBookingState::FeatureOff);

    enableOnlineBooking(['enabled' => false]);
    expect(VendorAvailability::for($this->vendor->fresh())->onlineState())->toBe(OnlineBookingState::GloballyOff);
});
