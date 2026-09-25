<?php

use App\Actions\ImportVendorIcal;
use App\Jobs\SyncVendorIcal;
use App\Models\Category;
use App\Models\Package;
use App\Models\Vendor;
use App\Models\VendorBookingSetting;
use App\Models\VendorUnavailableDate;
use App\Notifications\IcalSyncFailed;
use App\Support\IcalUrlGuard;
use App\Support\VendorAvailability;
use Database\Seeders\CategorySeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

const ICAL_URL = 'https://calendar.google.com/calendar/ical/studio/private-abc/basic.ics';

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    enableOnlineBooking();
    $this->travelTo(Carbon::parse('2026-11-01 09:00'));
    // Google resolves to a public address; no real DNS in tests.
    app()->instance(IcalUrlGuard::class, new IcalUrlGuard(fn (string $host): array => ['142.250.4.100']));

    $this->vendor = Vendor::factory()->for(Category::first())->takingOnlineBookings(['ical_url' => ICAL_URL])->create();
    $this->settings = $this->vendor->bookingSettings;
    Package::factory()->for($this->vendor)->create();
    $this->calendar = '';
    Http::fake(['calendar.google.com/*' => fn () => Http::response($this->calendar, 200, ['Content-Type' => 'text/calendar'])]);
});

/**
 * A calendar holding the given VEVENT bodies.
 */
function calendarWith(string ...$events): string
{
    $body = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Google Inc//Google Calendar//EN\r\n";

    foreach ($events as $at => $event) {
        $body .= "BEGIN:VEVENT\r\nUID:e{$at}@google.com\r\n".str_replace("\n", "\r\n", trim($event))."\r\nEND:VEVENT\r\n";
    }

    return $body."END:VCALENDAR\r\n";
}

/**
 * @return list<string>
 */
function importedDates(Vendor $vendor): array
{
    return $vendor->unavailableDates()->where('source', VendorUnavailableDate::SOURCE_ICAL)->get()
        ->map(fn (VendorUnavailableDate $row): string => $row->date->toDateString())->sort()->values()->all();
}

it('closes all-day events up to, not including, their end date', function () {
    $this->calendar = calendarWith(
        "DTSTART;VALUE=DATE:20261107\nDTEND;VALUE=DATE:20261108\nSUMMARY:Majlis Aina",
        "DTSTART;VALUE=DATE:20261114\nDTEND;VALUE=DATE:20261116\nSUMMARY:Dua hari",
    );

    expect(app(ImportVendorIcal::class)->handle($this->settings))->toBe(3)
        ->and(importedDates($this->vendor))->toBe(['2026-11-07', '2026-11-14', '2026-11-15']);
});

it('closes the Malaysian date a timed event falls on, even when UTC says otherwise', function () {
    // 18:00 UTC on the 8th is 02:00 on the 9th in Kuala Lumpur.
    $this->calendar = calendarWith("DTSTART:20261108T180000Z\nDTEND:20261108T200000Z\nSUMMARY:Lewat malam");

    app(ImportVendorIcal::class)->handle($this->settings);

    expect(importedDates($this->vendor))->toBe(['2026-11-09']);
});

it('expands repeating events and leaves out their exceptions', function () {
    $this->calendar = calendarWith("DTSTART;TZID=Asia/Kuala_Lumpur:20261107T100000\nDTEND;TZID=Asia/Kuala_Lumpur:20261107T120000\nRRULE:FREQ=WEEKLY;COUNT=3\nEXDATE;TZID=Asia/Kuala_Lumpur:20261114T100000\nSUMMARY:Setiap Sabtu");

    app(ImportVendorIcal::class)->handle($this->settings);

    expect(importedDates($this->vendor))->toBe(['2026-11-07', '2026-11-21']);
});

it('ignores events marked free or cancelled', function () {
    $this->calendar = calendarWith(
        "DTSTART;VALUE=DATE:20261110\nDTEND;VALUE=DATE:20261111\nTRANSP:TRANSPARENT\nSUMMARY:Peringatan",
        "DTSTART;VALUE=DATE:20261112\nDTEND;VALUE=DATE:20261113\nSTATUS:CANCELLED\nSUMMARY:Batal",
    );

    app(ImportVendorIcal::class)->handle($this->settings);

    expect(importedDates($this->vendor))->toBe([]);
});

it('replaces its own rows on every import but never a date closed by hand', function () {
    $this->vendor->unavailableDates()->create(['date' => '2026-11-07', 'source' => VendorUnavailableDate::SOURCE_MANUAL, 'reason' => 'Cuti']);
    $this->calendar = calendarWith("DTSTART;VALUE=DATE:20261107\nDTEND;VALUE=DATE:20261108", "DTSTART;VALUE=DATE:20261120\nDTEND;VALUE=DATE:20261121");
    app(ImportVendorIcal::class)->handle($this->settings);

    $this->calendar = calendarWith("DTSTART;VALUE=DATE:20261107\nDTEND;VALUE=DATE:20261108");
    app(ImportVendorIcal::class)->handle($this->settings);

    expect(importedDates($this->vendor))->toBe(['2026-11-07'])
        ->and($this->vendor->unavailableDates()->where('source', VendorUnavailableDate::SOURCE_MANUAL)->count())->toBe(1);
});

it('takes one place per event on a day that holds several', function () {
    $this->settings->update(['max_per_day' => 3]);
    $this->calendar = calendarWith("DTSTART;VALUE=DATE:20261121\nDTEND;VALUE=DATE:20261122", "DTSTART:20261121T020000Z\nDTEND:20261121T040000Z");

    app(ImportVendorIcal::class)->handle($this->settings->fresh());

    expect($this->vendor->unavailableDates()->sole()->slots)->toBe(2)
        ->and(VendorAvailability::for($this->vendor->fresh())->dayFor('2026-11-21')->value)->toBe('open');
});

it('refuses addresses inside the network or without https', function (string $url, array $resolved) {
    app()->instance(IcalUrlGuard::class, new IcalUrlGuard(fn (string $host): array => $resolved));
    $this->settings->update(['ical_url' => $url]);

    expect(fn () => app(ImportVendorIcal::class)->handle($this->settings->fresh()))->toThrow(RuntimeException::class, 'ical_url_refused');
    Http::assertNothingSent();
})->with([
    'plain http' => ['http://calendar.google.com/basic.ics', ['142.250.4.100']],
    'loopback literal' => ['https://127.0.0.1/basic.ics', []],
    'resolves to a private address' => ['https://calendar.example.com/basic.ics', ['10.0.0.8']],
    'another port' => ['https://calendar.google.com:8443/basic.ics', ['142.250.4.100']],
]);

it('keeps the imported dates when a later import fails, and tells the vendor once after three', function () {
    Notification::fake();
    $this->calendar = calendarWith("DTSTART;VALUE=DATE:20261107\nDTEND;VALUE=DATE:20261108");
    app(ImportVendorIcal::class)->handle($this->settings);

    $this->calendar = 'not a calendar';
    foreach (range(1, 4) as $attempt) {
        (new SyncVendorIcal($this->settings))->handle(app(ImportVendorIcal::class));
    }

    expect(importedDates($this->vendor))->toBe(['2026-11-07'])
        ->and($this->settings->fresh()->ical_error)->toBe('ical_unreadable');
    Notification::assertSentToTimes($this->vendor->user, IcalSyncFailed::class, 1);
});

it('counts a recent import as a confirmed calendar', function () {
    $this->settings->update(['calendar_confirmed_at' => now()->subDays(30)]);
    expect(VendorAvailability::for($this->vendor->fresh())->acceptsOnlineBookings())->toBeFalse();

    $this->calendar = calendarWith();
    app(ImportVendorIcal::class)->handle($this->settings->fresh());

    expect(VendorAvailability::for($this->vendor->fresh())->acceptsOnlineBookings())->toBeTrue();
});

it('lets the vendor connect and disconnect the calendar, keeping the address encrypted', function () {
    $this->settings->update(['ical_url' => null]);
    $this->calendar = calendarWith("DTSTART;VALUE=DATE:20261107\nDTEND;VALUE=DATE:20261108");

    $this->actingAs($this->vendor->user)->put(route('vendor.booking-settings.ical.connect'), ['ical_url' => ICAL_URL])->assertSessionHasNoErrors();

    expect(importedDates($this->vendor))->toBe(['2026-11-07'])
        ->and(DB::table('vendor_booking_settings')->value('ical_url'))->not->toContain('private-abc');

    $this->actingAs($this->vendor->user)->get(route('vendor.availability.index'))->assertDontSee('private-abc');

    $this->actingAs($this->vendor->user)->delete(route('vendor.booking-settings.ical.disconnect'));
    expect(importedDates($this->vendor))->toBe([])
        ->and($this->settings->fresh()->ical_url)->toBeNull();
});

it('does not keep an address that does not import', function () {
    $this->settings->update(['ical_url' => null]);
    $this->calendar = '<html>not found</html>';

    $this->actingAs($this->vendor->user)->put(route('vendor.booking-settings.ical.connect'), ['ical_url' => ICAL_URL])
        ->assertSessionHasErrors(['ical_url' => __('pages.booking_settings.ical_errors.ical_unreadable')]);

    expect($this->settings->fresh()->ical_url)->toBeNull();
});

it('queues an hourly import for every vendor with a calendar', function () {
    Queue::fake();
    VendorBookingSetting::factory()->for(Vendor::factory()->for(Category::first()))->create(['ical_url' => ICAL_URL]); // basic: skipped

    $this->artisan('neekah:sync-ical')->assertSuccessful();

    Queue::assertPushed(SyncVendorIcal::class, 1);
    expect(collect(app(Schedule::class)->events())->contains(fn ($event) => str_contains($event->command, 'neekah:sync-ical')))->toBeTrue();
});
