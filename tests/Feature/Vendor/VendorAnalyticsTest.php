<?php

use App\Actions\RecalculateVendorStats;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Enquiry;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->owner = User::factory()->create(['role' => UserRole::Vendor]);
    $this->vendor = Vendor::factory()->for(Category::first())->for($this->owner)->create();
});

it('shows revenue and completed majlis by month', function () {
    foreach ([1, 2] as $monthsAgo) {
        $booking = Booking::factory()->completed()->for($this->vendor)->create([
            'total_amount' => 2000,
            'completed_at' => now()->subMonths($monthsAgo),
        ]);

        Payment::factory()->for($booking)->create([
            'status' => PaymentStatus::Paid,
            'amount' => 2000,
            'paid_at' => now()->subMonths($monthsAgo),
        ]);
    }

    $this->actingAs($this->owner)->get(route('vendor.points.index'))
        ->assertOk()
        ->assertSee('RM4,000')
        ->assertSee('Pendapatan mengikut bulan')
        ->assertSee('Majlis selesai');
});

it('reports enquiries received against enquiries replied', function () {
    Enquiry::factory()->count(3)->for($this->vendor)->create(['created_at' => now()->subDays(3), 'replied_at' => now()->subDays(2)]);
    Enquiry::factory()->count(1)->for($this->vendor)->create(['created_at' => now()->subDays(3), 'replied_at' => null]);

    $this->actingAs($this->owner)->get(route('vendor.points.index'))
        ->assertOk()
        ->assertSee('3 / 4');
});

it('says a response rate is not measured yet instead of claiming one hundred percent', function (RecalculateVendorStats $recalculate) {
    expect($this->vendor->response_rate)->toBeNull();

    Enquiry::factory()->count(3)->for($this->vendor)->create(['created_at' => now()->subDays(3), 'replied_at' => now()]);
    $recalculate->handle($this->vendor);

    expect($this->vendor->fresh()->response_rate)->toBeNull()
        ->and($this->vendor->fresh()->responseRateLabel())->toBe('Belum diukur');

    $this->actingAs($this->owner)->get(route('vendor.points.index'))->assertOk()->assertSee('Belum diukur');
})->with([fn () => app(RecalculateVendorStats::class)]);

it('measures the response rate once there are enough enquiries to judge', function () {
    Enquiry::factory()->count(8)->for($this->vendor)->create(['created_at' => now()->subDays(3), 'replied_at' => now()->subDays(2)]);
    Enquiry::factory()->count(2)->for($this->vendor)->create(['created_at' => now()->subDays(3), 'replied_at' => null]);

    app(RecalculateVendorStats::class)->handle($this->vendor);

    expect($this->vendor->fresh()->response_rate)->toBe(80);
});

it('ignores enquiries too fresh to have been answered', function () {
    Enquiry::factory()->count(10)->for($this->vendor)->create(['created_at' => now()->subMinutes(5), 'replied_at' => null]);

    app(RecalculateVendorStats::class)->handle($this->vendor);

    expect($this->vendor->fresh()->response_rate)->toBeNull();
});

it('no longer lets an admin type a response rate in by hand', function () {
    Enquiry::factory()->count(10)->for($this->vendor)->create(['created_at' => now()->subDays(3), 'replied_at' => null]);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->put(route('admin.vendors.tier', $this->vendor), ['tier' => 'top', 'response_rate' => 100])
        ->assertRedirect();

    expect($this->vendor->fresh()->response_rate)->toBe(0);
});

it('lets the points table scroll rather than clipping the vendor own totals', function () {
    $response = $this->actingAs($this->owner)->get(route('vendor.points.index'))->assertOk();

    // The wrapper used to be overflow-hidden, which cut the right-hand column
    // off on a phone with no way to scroll it back into view.
    expect($response->getContent())->toMatch('/<div class="overflow-x-auto[^"]*">\s*<table/');
});
