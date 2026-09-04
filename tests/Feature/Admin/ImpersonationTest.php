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
    $this->customer = User::factory()->create(['name' => 'Aina Zulkifli']);
    $this->vendor = Vendor::factory()->for(Category::first())->create();
});

it('lets an admin impersonate a customer and land on their dashboard', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.users.impersonate', $this->customer))
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($this->customer);
    expect(session()->has('impersonated_by'))->toBeTrue();

    $this->get(route('dashboard'))->assertOk()->assertSee('Mod impersonate')->assertSee('Aina Zulkifli');
});

it('sends an impersonated vendor to the vendor dashboard', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.users.impersonate', $this->vendor->user))
        ->assertRedirect(route('vendor.dashboard'));

    $this->assertAuthenticatedAs($this->vendor->user);
});

it('returns the admin to their own account when they stop', function () {
    $this->actingAs($this->admin)->post(route('admin.users.impersonate', $this->customer));

    $this->post(route('impersonate.stop'))->assertRedirect(route('admin.users.index'));

    $this->assertAuthenticatedAs($this->admin);
    expect(session()->has('impersonated_by'))->toBeFalse();
});

it('never impersonates another admin', function () {
    $otherAdmin = User::factory()->admin()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.users.impersonate', $otherAdmin))
        ->assertForbidden();

    $this->assertAuthenticatedAs($this->admin);
});

it('keeps customers and vendors from impersonating anyone', function () {
    $this->actingAs($this->customer)->post(route('admin.users.impersonate', $this->vendor->user))->assertForbidden();
    $this->actingAs($this->vendor->user)->post(route('admin.users.impersonate', $this->customer))->assertForbidden();
});

it('sends a guest to login rather than impersonating', function () {
    $this->post(route('admin.users.impersonate', $this->customer))->assertRedirect(route('login'));
    $this->assertGuest();
});

it('blocks payments while impersonating', function () {
    $booking = Booking::factory()->for($this->customer)->for($this->vendor)->create(['deposit_amount' => 1000]);
    $deposit = Payment::factory()->for($booking)->create(['amount' => 1000]);

    $this->actingAs($this->admin)->post(route('admin.users.impersonate', $this->customer));

    $this->post(route('bookings.payments.store', [$booking, $deposit]))->assertSessionHasErrors('payment');

    expect($deposit->fresh()->isPaid())->toBeFalse();
});

it('shows an impersonate button only for impersonatable users', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.users.index'));

    $response->assertOk()->assertSee(route('admin.users.impersonate', $this->customer), false);
    $response->assertDontSee(route('admin.users.impersonate', $this->admin), false);
});

it('confirms in an in-app dialog rather than a browser prompt', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('<dialog id="confirm-', false)
        ->assertSee('data-dialog-open="confirm-', false)
        ->assertSee('Log masuk sebagai '.$this->customer->name.'?')
        ->assertSee('Ya, impersonate')
        ->assertDontSee('return confirm(', false);
});
