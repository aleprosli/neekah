<?php

namespace Database\Factories;

use App\Models\Wedding;
use App\Models\WeddingSite;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WeddingSite>
 */
class WeddingSiteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $bride = fake()->firstNameFemale();
        $groom = fake()->firstNameMale();

        return [
            'wedding_id' => Wedding::factory(),
            'subdomain' => Str::slug($bride.'-'.$groom).'-'.fake()->unique()->numberBetween(100, 999),
            'template' => 'klasik',
            'is_published' => false,
            'bride_name' => $bride,
            'groom_name' => $groom,
            'event_date' => fake()->dateTimeBetween('+2 months', '+1 year')->format('Y-m-d'),
            'starts_at' => '11:00',
            'ends_at' => '16:00',
            'venue_name' => 'Dewan '.fake()->lastName(),
            'rsvp_enabled' => true,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['is_published' => true]);
    }
}
