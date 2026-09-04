<?php

use App\Enums\EnquiryStatus;
use App\Models\Category;
use App\Models\Enquiry;
use App\Models\Package;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wedding;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->customer = User::factory()->create();
    $this->vendor = Vendor::factory()->for(Category::first())->create();
});

it('sends an enquiry and links it to the customer latest wedding', function () {
    $wedding = Wedding::factory()->for($this->customer)->create();
    $package = Package::factory()->for($this->vendor)->create();

    $this->actingAs($this->customer)
        ->post(route('vendors.enquiries.store', $this->vendor), [
            'message' => 'Masih ada slot untuk 20 Disember?',
            'event_date' => now()->addMonths(6)->toDateString(),
            'package_id' => $package->id,
        ])
        ->assertRedirect();

    $enquiry = Enquiry::sole();

    expect($enquiry->user_id)->toBe($this->customer->id)
        ->and($enquiry->vendor_id)->toBe($this->vendor->id)
        ->and($enquiry->wedding_id)->toBe($wedding->id)
        ->and($enquiry->package_id)->toBe($package->id)
        ->and($enquiry->status)->toBe(EnquiryStatus::Open);

    $this->actingAs($this->customer)->get(route('enquiries.index'))->assertOk()->assertSee($this->vendor->name);
    $this->actingAs($this->customer)->get(route('enquiries.show', $enquiry))->assertOk()->assertSee('Masih ada slot');
});

it('requires a message of reasonable length and a future date', function () {
    $this->actingAs($this->customer)
        ->post(route('vendors.enquiries.store', $this->vendor), ['message' => 'hi', 'event_date' => now()->subDay()->toDateString()])
        ->assertSessionHasErrors(['message', 'event_date']);
});

it('blocks guests, vendors and enquiries to unapproved vendors', function () {
    $pending = Vendor::factory()->pending()->for(Category::first())->create();

    $this->post(route('vendors.enquiries.store', $this->vendor), ['message' => 'Hello there vendor'])
        ->assertRedirect(route('login'));

    $this->actingAs($this->vendor->user)
        ->post(route('vendors.enquiries.store', $this->vendor), ['message' => 'Hello there vendor'])
        ->assertForbidden();

    $this->actingAs($this->customer)
        ->post(route('vendors.enquiries.store', $pending), ['message' => 'Hello there vendor'])
        ->assertNotFound();
});

it('shows the vendor reply to the customer', function () {
    $enquiry = Enquiry::factory()->replied()->for($this->customer)->for($this->vendor)->create(['reply' => 'Ya, masih ada slot.']);

    $this->actingAs($this->customer)->get(route('enquiries.show', $enquiry))->assertOk()->assertSee('Ya, masih ada slot.');
    $this->actingAs(User::factory()->create())->get(route('enquiries.show', $enquiry))->assertForbidden();
});
