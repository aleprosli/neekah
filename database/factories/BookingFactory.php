<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = fake()->numberBetween(5, 60) * 100;

        return [
            'reference' => Booking::generateReference(),
            'user_id' => User::factory(),
            'vendor_id' => Vendor::factory(),
            'wedding_id' => null,
            'package_id' => null,
            'package_name' => 'Premium Package',
            'event_date' => fake()->dateTimeBetween('+2 months', '+1 year')->format('Y-m-d'),
            'total_amount' => $total,
            'deposit_amount' => round($total * Booking::DEPOSIT_RATE, 2),
            'commission_rate' => Booking::COMMISSION_RATE,
            'commission_amount' => round($total * Booking::COMMISSION_RATE / 100, 2),
            'status' => BookingStatus::PendingPayment,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => [
            'status' => BookingStatus::Confirmed,
            'confirmed_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'event_date' => fake()->dateTimeBetween('-6 months', '-1 week')->format('Y-m-d'),
            'status' => BookingStatus::Completed,
            'confirmed_at' => now()->subMonths(2),
            'completed_at' => now()->subDays(3),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
