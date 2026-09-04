<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vendor;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wedding>
 */
class WeddingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->firstNameFemale().' & '.fake()->firstNameMale(),
            'event_date' => fake()->dateTimeBetween('+2 months', '+1 year')->format('Y-m-d'),
            'city' => fake()->city(),
            'state' => fake()->randomElement(Vendor::STATES),
            'budget' => fake()->numberBetween(10, 80) * 1000,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
