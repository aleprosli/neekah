<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
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
            'type' => PaymentType::Deposit,
            'amount' => fake()->numberBetween(2, 20) * 100,
            'status' => PaymentStatus::Pending,
            'gateway' => 'sandbox',
        ];
    }

    public function balance(): static
    {
        return $this->state(fn () => ['type' => PaymentType::Balance]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Paid,
            'gateway_reference' => 'SBX-'.fake()->bothify('########'),
            'paid_at' => now(),
        ]);
    }
}
