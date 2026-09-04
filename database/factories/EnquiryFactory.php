<?php

namespace Database\Factories;

use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enquiry>
 */
class EnquiryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vendor_id' => Vendor::factory(),
            'wedding_id' => null,
            'package_id' => null,
            'event_date' => fake()->dateTimeBetween('+2 months', '+1 year')->format('Y-m-d'),
            'message' => fake()->paragraph(),
            'status' => EnquiryStatus::Open,
        ];
    }

    public function replied(): static
    {
        return $this->state(fn () => [
            'reply' => fake()->paragraph(),
            'replied_at' => now(),
            'status' => EnquiryStatus::Replied,
        ]);
    }
}
