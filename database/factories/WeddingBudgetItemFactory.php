<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Wedding;
use App\Models\WeddingBudgetItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeddingBudgetItem>
 */
class WeddingBudgetItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'category_id' => Category::factory(),
            'planned_amount' => fake()->numberBetween(5, 100) * 100,
        ];
    }
}
