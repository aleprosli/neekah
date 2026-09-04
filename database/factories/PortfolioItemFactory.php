<?php

namespace Database\Factories;

use App\Models\PortfolioItem;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PortfolioItem>
 */
class PortfolioItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'path' => 'portfolio/'.fake()->uuid().'.jpg',
            'caption' => fake()->sentence(3),
            'sort_order' => 0,
        ];
    }
}
