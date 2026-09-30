<?php

use App\Enums\PriceUnit;
use App\Models\Category;
use App\Models\Vendor;
use App\Models\VendorBookingSetting;
use App\Notifications\CalendarReminder;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    enableOnlineBooking();
    config()->set('services.herepay.base_url', 'https://uat.herepay.org');
    $this->vendor = Vendor::factory()->for(Category::first())->create(['pro_until' => now()->addYear()]);
});

/**
 * @return array<string, mixed>
 */
function bookingRules(array $overrides = []): array
{
    return [
        'enabled' => '1', 'deposit_type' => 'percent', 'deposit_value' => 30, 'max_per_day' => 2,
        'available_weekdays' => [6, 7], 'min_lead_days' => 30, 'max_advance_months' => 12,
        'manual_instructions' => 'CIMB 8000 1234 · Studio Nur', ...$overrides,
    ];
}

it('sends a basic vendor to the Pro page instead of the booking settings', function () {
    $basic = Vendor::factory()->for(Category::first())->create();

    $this->actingAs($basic->user)->get(route('vendor.availability.index'))->assertRedirect(route('vendor.pro.index'));
    $this->actingAs($basic->user)->put(route('vendor.booking-settings.update'), bookingRules())->assertForbidden();
});

it('saves the rules, and saving counts as confirming the calendar', function () {
    $this->actingAs($this->vendor->user)->get(route('vendor.availability.index'))->assertOk();
    $this->actingAs($this->vendor->user)->put(route('vendor.booking-settings.update'), bookingRules())->assertSessionHasNoErrors();

    $settings = $this->vendor->bookingSettings()->sole();

    expect($settings->enabled)->toBeTrue()
        ->and($settings->weekdays())->toBe([6, 7])
        ->and($settings->max_per_day)->toBe(2)
        ->and($settings->calendar_confirmed_at)->not->toBeNull();
});

it('makes a per-pax vendor take a fixed deposit', function () {
    $this->vendor->update(['price_unit' => PriceUnit::Pax]);

    $this->actingAs($this->vendor->user)->put(route('vendor.booking-settings.update'), bookingRules())
        ->assertSessionHasErrors(['deposit_type' => __('validation.custom.pax_needs_fixed_deposit')]);
});

it('keeps Herepay keys only once Herepay accepts them, encrypted and never shown again', function () {
    Http::fake(['uat.herepay.org/*' => Http::sequence()
        ->push(['status' => 'Unauthorized'], 401)
        ->push(['status' => 200, 'data' => ['pay_url' => 'https://uat.herepay.org/herepay/pay/T']])]);

    $keys = ['herepay_secret_key' => 'sk_live_vendor_123', 'herepay_private_key' => 'pk_live_vendor_456'];

    $this->actingAs($this->vendor->user)->put(route('vendor.booking-settings.herepay.connect'), $keys)->assertSessionHasErrors('herepay_secret_key');
    expect(VendorBookingSetting::count())->toBe(0);

    $this->actingAs($this->vendor->user)->put(route('vendor.booking-settings.herepay.connect'), $keys)->assertSessionHasNoErrors();

    $settings = $this->vendor->bookingSettings()->sole();
    expect($settings->hasHerepay())->toBeTrue()
        ->and(DB::table('vendor_booking_settings')->value('herepay_secret_key'))->not->toContain('sk_live_vendor_123');

    $this->actingAs($this->vendor->user)->get(route('vendor.availability.index'))
        ->assertDontSee('sk_live_vendor_123')
        ->assertDontSee('pk_live_vendor_456');

    $this->actingAs($this->vendor->user)->delete(route('vendor.booking-settings.herepay.disconnect'));
    expect($settings->fresh()->hasHerepay())->toBeFalse();
});

it('confirms the calendar in one click, and closing a date counts too', function () {
    $this->actingAs($this->vendor->user)->post(route('vendor.booking-settings.calendar'))->assertRedirect();
    expect($this->vendor->bookingSettings()->sole()->calendar_confirmed_at)->not->toBeNull();

    $this->travel(10)->days();
    $this->actingAs($this->vendor->user)->post(route('vendor.availability.store'), ['from' => now()->addMonth()->toDateString(), 'reason' => 'Majlis WhatsApp']);

    expect($this->vendor->bookingSettings()->sole()->calendar_confirmed_at->isToday())->toBeTrue();
});

it('lets an outside booking take one place on a day that holds several', function () {
    $date = now()->addMonth()->toDateString();

    $this->actingAs($this->vendor->user)->post(route('vendor.availability.store'), ['from' => $date, 'slots' => 1])->assertSessionHasNoErrors();

    expect($this->vendor->unavailableDates()->sole()->slots)->toBe(1);
});

it('reminds a vendor a week after confirming, before online booking pauses, and when it does', function () {
    Notification::fake();
    VendorBookingSetting::factory()->for($this->vendor)->create();
    $basic = Vendor::factory()->for(Category::first())->create();
    VendorBookingSetting::factory()->for($basic)->create();

    foreach (range(1, 15) as $day) {
        $this->travel(1)->days();
        $this->artisan('neekah:calendar-reminders');
    }

    Notification::assertSentToTimes($this->vendor->user, CalendarReminder::class, 3);
    Notification::assertNotSentTo($basic->user, CalendarReminder::class);
});

it('shows the calendar, the booking rules, the deposit and Google Calendar on one page', function () {
    $props = $this->actingAs($this->vendor->user)->get(route('vendor.availability.index'))->assertOk()->viewData('props');

    expect(array_keys($props))->toContain('status', 'calendar', 'rules', 'deposit', 'ical')
        ->and($props['rules']['url'])->toBe(route('vendor.booking-settings.update'))
        ->and($props['calendar']['storeUrl'])->toBe(route('vendor.availability.store'));

    // Links to the old settings page land on its tab.
    $this->actingAs($this->vendor->user)->get('/vendor/tempahan-online')->assertRedirect('/vendor/availability?tab=tempahan');
});

it('turns online booking on only once couples have a way to pay the deposit', function () {
    $this->actingAs($this->vendor->user->fresh())->put(route('vendor.booking-settings.toggle'), ['enabled' => 1])
        ->assertSessionHasErrors(['enabled' => __('flash.vendor.online_needs_payment')]);
    expect($this->vendor->bookingSettings()->value('enabled'))->toBeFalsy();

    $this->actingAs($this->vendor->user->fresh())->put(route('vendor.booking-settings.manual'), ['manual_instructions' => 'Maybank 1234 · Studio Nur'])->assertSessionHasNoErrors();
    $this->actingAs($this->vendor->user->fresh())->put(route('vendor.booking-settings.toggle'), ['enabled' => 1])->assertSessionHasNoErrors();

    $settings = $this->vendor->bookingSettings()->sole();
    expect($settings->enabled)->toBeTrue()
        ->and($settings->manual_instructions)->toBe('Maybank 1234 · Studio Nur');

    // Saving the rules without the switch leaves it where it is.
    $this->actingAs($this->vendor->user->fresh())->put(route('vendor.booking-settings.update'), collect(bookingRules())->except('enabled', 'manual_instructions')->all())->assertSessionHasNoErrors();
    expect($settings->fresh()->enabled)->toBeTrue()
        ->and($settings->fresh()->manual_instructions)->toBe('Maybank 1234 · Studio Nur');

    $this->actingAs($this->vendor->user->fresh())->put(route('vendor.booking-settings.toggle'), ['enabled' => 0]);
    expect($settings->fresh()->enabled)->toBeFalse();
});

it('guides the vendor through four steps, with where to sign up for Herepay', function () {
    $props = $this->actingAs($this->vendor->user)->get(route('vendor.availability.index'))->viewData('props');

    expect($props['steps'])->toBe(['deposit' => false, 'rules' => false, 'calendar' => false, 'live' => false])
        ->and($props['deposit']['registerUrl'])->toBe('https://app.herepay.org/register');
});
