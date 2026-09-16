<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => Payment::generateReference(),
            'booking_id' => Booking::factory(),
            'amount' => fake()->numberBetween(2, 20) * 100,
            'paid_on' => now()->toDateString(),
            'method' => 'manual_transfer',
            'status' => PaymentStatus::AwaitingVerification,
            'gateway' => 'manual',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'verified_at' => now(),
        ]);
    }
}
