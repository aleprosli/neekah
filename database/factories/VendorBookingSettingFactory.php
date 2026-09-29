<?php

namespace Database\Factories;

use App\Models\Vendor;
use App\Models\VendorBookingSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorBookingSetting>
 */
class VendorBookingSettingFactory extends Factory
{
    /**
     * Switched on, open every day from tomorrow, a manual deposit path and a
     * calendar confirmed today: a vendor ready to take a booking.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'enabled' => true,
            'deposit_type' => 'percent',
            'deposit_value' => 30,
            'max_per_day' => 1,
            'available_weekdays' => VendorBookingSetting::ALL_WEEKDAYS,
            'min_lead_days' => 1,
            'max_advance_months' => 18,
            'manual_instructions' => 'Maybank 1234 5678 9012 · '.fake()->company(),
            'calendar_confirmed_at' => now(),
        ];
    }

    public function withHerepay(): static
    {
        return $this->state(fn (): array => [
            'herepay_secret_key' => 'vendor-secret',
            'herepay_private_key' => 'vendor-private',
            'herepay_connected_at' => now(),
        ]);
    }
}
