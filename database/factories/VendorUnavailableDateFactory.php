<?php

namespace Database\Factories;

use App\Models\Vendor;
use App\Models\VendorUnavailableDate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorUnavailableDate>
 */
class VendorUnavailableDateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'date' => fake()->dateTimeBetween('+1 week', '+1 year')->format('Y-m-d'),
            'reason' => fake()->optional()->sentence(3),
        ];
    }
}
