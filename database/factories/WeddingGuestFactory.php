<?php

namespace Database\Factories;

use App\Enums\GuestGroup;
use App\Enums\GuestSide;
use App\Models\Wedding;
use App\Models\WeddingGuest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeddingGuest>
 */
class WeddingGuestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'name' => fake()->name(),
            'phone' => '01'.fake()->numerify('#-### ####'),
            'side' => fake()->randomElement(GuestSide::cases())->value,
            'group' => fake()->randomElement(GuestGroup::cases())->value,
            'pax_invited' => fake()->numberBetween(1, 4),
            'notes' => null,
        ];
    }

    public function shared(): static
    {
        return $this->state(fn (): array => ['shared_at' => now()->subDay()]);
    }
}
