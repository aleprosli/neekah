<?php

namespace Database\Factories;

use App\Models\Wedding;
use App\Models\WeddingTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeddingTask>
 */
class WeddingTaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'title' => fake()->sentence(4),
            'due_date' => fake()->dateTimeBetween('+1 week', '+6 months')->format('Y-m-d'),
            'sort_order' => 0,
        ];
    }

    public function done(): static
    {
        return $this->state(fn () => ['completed_at' => now()]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => ['due_date' => now()->subWeek()->toDateString(), 'completed_at' => null]);
    }
}
