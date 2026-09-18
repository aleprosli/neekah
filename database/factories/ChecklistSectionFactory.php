<?php

namespace Database\Factories;

use App\Models\ChecklistSection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChecklistSection>
 */
class ChecklistSectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => Str::title(fake()->unique()->words(2, true)),
            'icon' => fake()->randomElement(['🗓️', '📄', '🩺', '👨‍👩‍👧', '👰', '🕌', '🎉', '🏠']),
            'note' => null,
            'sort_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }
}
