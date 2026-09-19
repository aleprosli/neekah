<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Review;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory()->completed(),
            'user_id' => fn (array $attributes) => Booking::find($attributes['booking_id'])->user_id,
            'vendor_id' => fn (array $attributes) => Booking::find($attributes['booking_id'])->vendor_id,
            'rating' => fake()->numberBetween(4, 5),
            'quality' => fake()->numberBetween(4, 5),
            'service' => fake()->numberBetween(4, 5),
            'communication' => fake()->numberBetween(4, 5),
            'value' => fake()->numberBetween(3, 5),
            'punctuality' => fake()->numberBetween(4, 5),
            'comment' => fake()->paragraph(),
        ];
    }

    /**
     * Written straight on the profile by a guest: no booking, no account, and
     * none of the five aspects.
     */
    public function open(): static
    {
        return $this->state(fn (): array => [
            'booking_id' => null,
            'user_id' => null,
            'vendor_id' => Vendor::factory(),
            'author_name' => fake()->name(),
            'author_email' => fake()->optional()->safeEmail(),
            'quality' => null,
            'service' => null,
            'communication' => null,
            'value' => null,
            'punctuality' => null,
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn (): array => [
            'hidden_at' => now(),
            'hidden_reason' => 'Spam',
        ]);
    }
}
