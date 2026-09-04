<?php

use App\Models\Booking;
use App\Models\Category;
use App\Models\Package;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\BookingCompleted;
use App\Notifications\BookingCreatedForCustomer;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->customer = User::factory()->create();
    $this->vendor = Vendor::factory()->for(Category::first())->create();
    $this->package = Package::factory()->for($this->vendor)->create(['price' => 2500]);
});

it('stores a notification for both sides when a booking is made', function () {
    $this->actingAs($this->customer)
        ->post(route('vendors.bookings.store', $this->vendor), ['package_id' => $this->package->id, 'event_date' => now()->addMonths(3)->toDateString()])
        ->assertRedirect();

    expect($this->customer->notifications()->count())->toBe(1)
        ->and($this->vendor->user->notifications()->count())->toBe(1);

    $notification = $this->customer->notifications()->first();
    expect($notification->data)->toHaveKeys(['icon', 'title', 'body', 'url'])
        ->and($notification->data['title'])->toContain('dibuat');
});

it('shows an unread badge in the header and lists notifications', function () {
    $this->actingAs($this->customer)
        ->post(route('vendors.bookings.store', $this->vendor), ['package_id' => $this->package->id, 'event_date' => now()->addMonths(3)->toDateString()]);

    $this->actingAs($this->customer)
        ->get(route('vendors.index'))
        ->assertOk()
        ->assertSee('Notifikasi');

    $this->actingAs($this->customer)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('1 belum dibaca')
        ->assertSee('Booking');
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
