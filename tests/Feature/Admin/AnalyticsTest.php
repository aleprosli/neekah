<?php

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->vendor = Vendor::factory()->for(Category::first())->create();
});

it('totals the same money the transaction page reports', function () {
    foreach ([1, 3, 5] as $monthsAgo) {
        $booking = Booking::factory()->confirmed()->for($this->vendor)->create([
            'total_amount' => 1000,
            'commission_amount' => 100,
            'confirmed_at' => now()->subMonths($monthsAgo),
            'created_at' => now()->subMonths($monthsAgo),
        ]);

        Payment::factory()->for($booking)->create([
            'status' => PaymentStatus::Paid,
            'amount' => 1000,
            'paid_at' => now()->subMonths($monthsAgo),
        ]);
    }

    $this->actingAs($this->admin)->get(route('admin.analytics'))
        ->assertOk()
        ->assertSee('RM3,000')
        ->assertSee('RM300');
});

it('leaves out anything older than the chosen window', function () {
    $booking = Booking::factory()->confirmed()->for($this->vendor)->create([
        'total_amount' => 5000,
        'commission_amount' => 500,
        'confirmed_at' => now()->subMonths(9),
        'created_at' => now()->subMonths(9),
    ]);
    Payment::factory()->for($booking)->create(['status' => PaymentStatus::Paid, 'amount' => 5000, 'paid_at' => now()->subMonths(9)]);

    $this->actingAs($this->admin)->get(route('admin.analytics', ['months' => 3]))
        ->assertOk()
        ->assertSee('3 bulan')
        ->assertDontSee('RM5,000');

    $this->actingAs($this->admin)->get(route('admin.analytics', ['months' => 12]))
        ->assertOk()
        ->assertSee('RM5,000');
});

it('falls back to twelve months when the window is nonsense', function () {
    $this->actingAs($this->admin)->get(route('admin.analytics', ['months' => 999]))
        ->assertOk()
        ->assertSee('12 bulan');
});

it('says there is no data rather than drawing an empty chart', function () {
    $this->actingAs($this->admin)->get(route('admin.analytics'))
        ->assertOk()
        ->assertSee('Tiada data untuk tempoh ini.');
});

it('keeps analytics to admins', function () {
    $this->actingAs(User::factory()->create())->get(route('admin.analytics'))->assertForbidden();
});

it('turns a signed out visitor away from analytics', function () {
    $this->get(route('admin.analytics'))->assertRedirect(route('login'));
});
