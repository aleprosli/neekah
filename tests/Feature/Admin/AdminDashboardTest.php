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

it('opens Kewangan with Neekah revenue apart from what went to vendors', function () {
    $vendor = Vendor::factory()->for(Category::first())->create();
    $booking = Booking::factory()->completed()->for($vendor)->create(['total_amount' => 1000]);
    Payment::factory()->for($booking)->paid()->create(['amount' => 1000]);
    Payment::factory()->pro()->paid()->create(['vendor_id' => $vendor->id, 'amount' => 49, 'paid_at' => now()]);

    $props = $this->actingAs($this->admin)->get(route('admin.payments.index'))->assertOk()->viewData('props');

    expect(collect($props['stats'])->pluck('value', 'label')->all())->toMatchArray([
        __('pages.payments.stat_neekah_all') => 'RM49.00',
    ]);
});
