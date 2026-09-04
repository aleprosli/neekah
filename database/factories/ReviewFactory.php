<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Review;
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
}
