<?php

namespace Database\Factories;

use App\Models\WeddingRsvp;
use App\Models\WeddingSite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeddingRsvp>
 */
class WeddingRsvpFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wedding_site_id' => WeddingSite::factory(),
            'name' => fake()->name(),
            'phone' => fake()->numerify('01#-### ####'),
            'attending' => true,
            'pax' => fake()->numberBetween(1, 5),
        ];
    }

    public function declined(): static
    {
        return $this->state(fn () => ['attending' => false, 'pax' => 0]);
    }
}
