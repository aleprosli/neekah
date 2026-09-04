<?php

namespace Database\Factories;

use App\Models\Wedding;
use App\Models\WeddingTimelineItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeddingTimelineItem>
 */
class WeddingTimelineItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'vendor_id' => null,
            'starts_at' => fake()->time('H:i'),
            'title' => fake()->randomElement(['Makeup', 'Akad Nikah', 'Bersanding', 'Photography session', 'Majlis tamat']),
        ];
    }
}
