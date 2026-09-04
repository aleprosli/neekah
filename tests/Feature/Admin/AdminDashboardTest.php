<?php

use App\Models\Booking;
use App\Models\Category;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->admin = User::factory()->admin()->create();
});

it('keeps guests, customers and vendors out of the admin area', function () {
    $vendor = Vendor::factory()->for(Category::first())->create();

    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($vendor->user)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs(User::factory()->create())->get(route('admin.vendors.index'))->assertForbidden();
});

it('shows platform totals, gross value and commission', function () {
    $customer = User::factory()->create();
    $vendor = Vendor::factory()->for(Category::first())->create(['name' => 'ABC Studio']);
    Vendor::factory()->pending()->for(Category::first())->create(['name' => 'Pending Studio']);

    $booking = Booking::factory()->confirmed()->for($customer)->for($vendor)->create([
        'total_amount' => 2500,
        'deposit_amount' => 1000,
        'commission_amount' => 200,
    ]);
    Payment::factory()->for($booking)->paid()->create(['amount' => 1000]);

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Ringkasan platform')
        ->assertSee('RM200.00')
        ->assertSee('RM1,000.00')
        ->assertSee('Pending Studio')
        ->assertSee($booking->reference);
});

it('lists transactions with gross, commission and payout', function () {
    $vendor = Vendor::factory()->for(Category::first())->create();
    $booking = Booking::factory()->completed()->for($vendor)->create(['total_amount' => 1000, 'commission_amount' => 80]);
    Payment::factory()->for($booking)->paid()->create(['amount' => 1000]);

    $this->actingAs($this->admin)
        ->get(route('admin.transactions.index'))
        ->assertOk()
        ->assertSee('Gross transaction value')
        ->assertSee('RM1,000.00')
        ->assertSee('RM80.00')
        ->assertSee('RM920.00');
});
