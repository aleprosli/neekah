<?php

use App\Models\Booking;
use App\Models\Category;
use App\Models\Package;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\BookingCompleted;
use App\Notifications\BookingCreatedForCustomer;
use App\Support\StoredNotification;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    // Online booking is a Neekah Pro feature and off site-wide by default;
    // these vendors take it, so the couple's booking flow is live.
    enableOnlineBooking();

    $this->seed(CategorySeeder::class);
    $this->customer = User::factory()->create();
    $this->vendor = Vendor::factory()->for(Category::first())->takingOnlineBookings()->create();
    $this->package = Package::factory()->for($this->vendor)->create(['price' => 2500]);
});

it('stores a notification for both sides when a booking is made', function () {
    $this->actingAs($this->customer)
        ->post(route('vendors.bookings.store', $this->vendor), ['package_id' => $this->package->id, 'event_date' => now()->addMonths(3)->toDateString()])
        ->assertRedirect();

    expect($this->customer->notifications()->count())->toBe(1)
        ->and($this->vendor->user->notifications()->count())->toBe(1);

    // The row stores what happened, not a finished sentence, so somebody who
    // switches language sees their whole history in it.
    $notification = $this->customer->notifications()->first();
    expect($notification->data)->toHaveKeys(['icon', 'title_key', 'url'])
        ->and(StoredNotification::render($notification->data)['title'])->toContain('menunggu deposit');

    app()->setLocale('en');
    expect(StoredNotification::render($notification->data)['title'])->toContain('waiting for its deposit');
    app()->setLocale('ms');
});

it('shows an unread badge in the header and lists notifications', function () {
    $this->actingAs($this->customer)
        ->post(route('vendors.bookings.store', $this->vendor), ['package_id' => $this->package->id, 'event_date' => now()->addMonths(3)->toDateString()]);

    $this->actingAs($this->customer)
        ->get(route('vendors.index'))
        ->assertOk()
        ->assertSee('Notifikasi');

    $props = $this->actingAs($this->customer)
        ->get(route('notifications.index'))
        ->assertOk()
        ->viewData('props');

    expect($props['unreadCount'])->toBe(1)
        ->and($props['notifications'][0]['unread'])->toBeTrue()
        ->and($props['notifications'][0]['title'])->toContain('Tempahan');
});

it('marks one notification read and follows it to its target', function () {
    $booking = Booking::factory()->for($this->customer)->for($this->vendor)->create();
    $this->customer->notify(new BookingCreatedForCustomer($booking));

    $notification = $this->customer->notifications()->first();

    $this->actingAs($this->customer)
        ->get(route('notifications.show', $notification->id))
        ->assertRedirect(route('bookings.show', $booking));

    expect($this->customer->fresh()->unreadNotifications()->count())->toBe(0);
});

it('marks everything read at once', function () {
    $booking = Booking::factory()->for($this->customer)->for($this->vendor)->create();
    $this->customer->notify(new BookingCreatedForCustomer($booking));
    $this->customer->notify(new BookingCompleted($booking));

    expect($this->customer->unreadNotifications()->count())->toBe(2);

    $this->actingAs($this->customer)->put(route('notifications.read'))->assertRedirect();

    expect($this->customer->fresh()->unreadNotifications()->count())->toBe(0);
});

it('never shows one user another user notifications', function () {
    $booking = Booking::factory()->for($this->customer)->for($this->vendor)->create();
    $this->customer->notify(new BookingCreatedForCustomer($booking));
    $notification = $this->customer->notifications()->first();

    $this->actingAs(User::factory()->create())
        ->get(route('notifications.show', $notification->id))
        ->assertNotFound();
});

it('keeps guests out of the notification centre', function () {
    $this->get(route('notifications.index'))->assertRedirect(route('login'));
});
