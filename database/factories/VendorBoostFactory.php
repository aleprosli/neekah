<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Vendor;
use App\Models\VendorBoost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorBoost>
 */
class VendorBoostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'category_id' => Category::factory(),
            'starts_at' => now()->startOfHour(),
            'ends_at' => now()->startOfHour()->addDays(3),
            'tokens' => 3,
        ];
    }
}
