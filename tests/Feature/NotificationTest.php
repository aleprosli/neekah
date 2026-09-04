<?php

use App\Models\Booking;
use App\Models\Category;
use App\Models\Enquiry;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\BookingCompleted;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingCreatedForCustomer;
use App\Notifications\BookingCreatedForVendor;
use App\Notifications\EnquiryReceived;
use App\Notifications\EnquiryReplied;
use App\Notifications\PaymentReceived;
use App\Notifications\VendorStatusChanged;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    $this->seed(CategorySeeder::class);
    $this->customer = User::factory()->create();
    $this->vendor = Vendor::factory()->for(Category::first())->create();
    $this->package = Package::factory()->for($this->vendor)->create(['price' => 2500]);
});

it('emails both parties when a booking is created', function () {
    $this->actingAs($this->customer)
        ->post(route('vendors.bookings.store', $this->vendor), [
            'package_id' => $this->package->id,
            'event_date' => now()->addMonths(3)->toDateString(),
        ])
        ->assertRedirect();

    Notification::assertSentTo($this->customer, BookingCreatedForCustomer::class);
    Notification::assertSentTo($this->vendor->user, BookingCreatedForVendor::class);
});

it('emails a receipt and a confirmation when the deposit is paid', function () {
    $booking = Booking::factory()->for($this->customer)->for($this->vendor)->create(['total_amount' => 2500, 'deposit_amount' => 1000]);
    $deposit = Payment::factory()->for($booking)->create(['amount' => 1000]);

    $this->actingAs($this->customer)->post(route('bookings.payments.store', [$booking, $deposit]))->assertRedirect();

    Notification::assertSentTo($this->customer, PaymentReceived::class);
    Notification::assertSentTo($this->customer, BookingConfirmed::class);
    Notification::assertSentTo($this->vendor->user, BookingConfirmed::class);
});

it('emails a review reminder when the vendor completes the booking', function () {
    $booking = Booking::factory()->confirmed()->for($this->customer)->for($this->vendor)->create(['event_date' => now()->subDays(2)->toDateString()]);

    $this->actingAs($this->vendor->user)->post(route('vendor.bookings.complete', $booking))->assertRedirect();

    Notification::assertSentTo($this->customer, BookingCompleted::class);
});

it('emails the vendor on a new enquiry and the customer on the reply', function () {
    $this->actingAs($this->customer)
        ->post(route('vendors.enquiries.store', $this->vendor), ['message' => 'Masih ada slot untuk 20 Disember?'])
        ->assertRedirect();

    Notification::assertSentTo($this->vendor->user, EnquiryReceived::class);

    $enquiry = Enquiry::sole();

    $this->actingAs($this->vendor->user)
        ->put(route('vendor.enquiries.update', $enquiry), ['reply' => 'Ya, masih ada slot.'])
        ->assertRedirect();

    Notification::assertSentTo($this->customer, EnquiryReplied::class);
});

it('emails the vendor owner when an admin changes their status', function () {
    $admin = User::factory()->admin()->create();
    $pending = Vendor::factory()->pending()->for(Category::first())->create();

    $this->actingAs($admin)->post(route('admin.vendors.status', $pending), ['status' => 'approved'])->assertRedirect();

    Notification::assertSentTo($pending->user, VendorStatusChanged::class);
});
