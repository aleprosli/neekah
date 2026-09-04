<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'name' => fake()->randomElement(['Basic Package', 'Standard Package', 'Premium Package', 'Deluxe Package']),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(5, 60) * 100,
            'duration' => fake()->randomElement(['4 jam', '6 jam', '8 jam', '1 hari']),
            'features' => fake()->words(4),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
