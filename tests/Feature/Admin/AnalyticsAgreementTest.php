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

/**
 * The analytics page and the overview dashboard must never quote different
 * money for the same period, or nobody can tell which one to believe.
 */
it('quotes the same gross and commission as the overview dashboard', function () {
    foreach ([0, 2, 4] as $monthsAgo) {
        $booking = Booking::factory()->confirmed()->for($this->vendor)->create([
            'total_amount' => 1200,
            'commission_amount' => 120,
            'confirmed_at' => now()->subMonths($monthsAgo)->startOfMonth()->addDays(3),
            'created_at' => now()->subMonths($monthsAgo)->startOfMonth()->addDays(3),
        ]);

        Payment::factory()->for($booking)->create([
            'status' => PaymentStatus::Paid,
            'amount' => 1200,
            'paid_at' => now()->subMonths($monthsAgo)->startOfMonth()->addDays(4),
        ]);
    }

    $gross = 'RM'.number_format(3600);
    $commission = 'RM'.number_format(360);

    $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->assertSee($gross)->assertSee($commission);
    $this->actingAs($this->admin)->get(route('admin.analytics'))->assertOk()->assertSee($gross)->assertSee($commission);
});
