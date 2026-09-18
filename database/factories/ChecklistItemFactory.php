<?php

namespace Database\Factories;

use App\Models\ChecklistItem;
use App\Models\ChecklistSection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChecklistItem>
 */
class ChecklistItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'checklist_section_id' => ChecklistSection::factory(),
            'category_id' => null,
            'group' => null,
            'title' => Str::ucfirst(fake()->unique()->words(3, true)),
            'notes' => null,
            'months_before' => fake()->numberBetween(1, 12),
            'sort_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }
}
