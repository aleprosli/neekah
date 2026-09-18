<?php

use App\Models\Booking;
use App\Models\Category;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;

it('clears the commission recorded on bookings made before Neekah went free', function () {
    $this->seed(CategorySeeder::class);
    $vendor = Vendor::factory()->for(Category::first())->create();
    $older = Booking::factory()->for($vendor)->create(['total_amount' => 3000, 'commission_rate' => 8, 'commission_amount' => 240]);
    $newer = Booking::factory()->for($vendor)->create(['total_amount' => 1500, 'commission_rate' => 0, 'commission_amount' => 0]);

    $migration = require database_path('migrations/2026_09_18_150450_clear_commission_on_existing_bookings.php');
    $migration->up();

    expect((float) $older->fresh()->commission_rate)->toBe(0.0)
        ->and((float) $older->fresh()->commission_amount)->toBe(0.0)
        ->and((float) $older->fresh()->total_amount)->toBe(3000.0)
        ->and($older->fresh()->hasCommission())->toBeFalse()
        ->and((float) $newer->fresh()->total_amount)->toBe(1500.0);
});
