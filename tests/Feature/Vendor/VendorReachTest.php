<?php

use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDailyStat;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->owner = User::factory()->vendor()->create();
    $this->vendor = Vendor::factory()->for(Category::first())->for($this->owner)->create(['whatsapp' => '60123456789']);
});

function reach(Vendor $vendor, string $counter): int
{
    return (int) VendorDailyStat::query()->whereBelongsTo($vendor)->sum($counter);
}

it('counts a profile view once per visitor session', function () {
    $this->get(route('vendors.show', $this->vendor))->assertOk();
    $this->get(route('vendors.show', $this->vendor))->assertOk();

    expect(reach($this->vendor, 'profile_views'))->toBe(1);
});

it('does not count the vendor, an admin or a crawler as a view', function () {
    $this->actingAs($this->owner)->get(route('vendors.show', $this->vendor));
    $this->actingAs(User::factory()->admin()->create())->get(route('vendors.show', $this->vendor));
    auth()->logout();
    $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1)')->get(route('vendors.show', $this->vendor));

    expect(reach($this->vendor, 'profile_views'))->toBe(0);
});

it('counts a WhatsApp tap and sends the couple on to the vendor', function () {
    $couple = User::factory()->create();

    $this->actingAs($couple)
        ->get(route('vendors.contact.whatsapp', $this->vendor))
        ->assertRedirectContains('https://wa.me/60123456789');

    expect(reach($this->vendor, 'whatsapp_clicks'))->toBe(1);
});

it('keeps the WhatsApp redirect behind sign-in like the number itself', function () {
    $this->get(route('vendors.contact.whatsapp', $this->vendor))->assertRedirect(route('login'));

    expect(reach($this->vendor, 'whatsapp_clicks'))->toBe(0);
});

it('counts a tap on the phone number', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('vendors.contact.phone', $this->vendor))
        ->assertNoContent();

    expect(reach($this->vendor, 'phone_clicks'))->toBe(1);
});

it('shows a free vendor last weeks totals and keeps the chart for Pro', function () {
    VendorDailyStat::create(['vendor_id' => $this->vendor->id, 'date' => today()->subDays(2), 'profile_views' => 12]);
    VendorDailyStat::create(['vendor_id' => $this->vendor->id, 'date' => today()->subDays(20), 'profile_views' => 30]);

    $this->actingAs($this->owner)->get(route('vendor.pro.index'))
        ->assertOk()
        ->assertSee(__('pages.pro.analytics_teaser'))
        ->assertDontSee(__('pages.pro.daily_views'))
        ->assertViewHas('totals', fn (array $totals): bool => $totals['profile_views'] === 12);

    $this->vendor->update(['pro_until' => now()->addMonth()]);

    $this->actingAs($this->owner->fresh())->get(route('vendor.pro.index'))
        ->assertSee(__('pages.pro.daily_views'))
        ->assertViewHas('totals', fn (array $totals): bool => $totals['profile_views'] === 42);
});
