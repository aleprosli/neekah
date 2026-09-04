<?php

namespace Database\Factories;

use App\Enums\ViolationStatus;
use App\Enums\ViolationType;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorViolation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorViolation>
 */
class VendorViolationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'reported_by' => User::factory(),
            'booking_id' => null,
            'type' => ViolationType::PaymentBypass,
            'description' => fake()->paragraph(),
            'status' => ViolationStatus::Open,
        ];
    }

    public function upheld(): static
    {
        return $this->state(fn () => [
            'status' => ViolationStatus::Upheld,
            'resolved_at' => now(),
        ]);
    }

    public function dismissed(): static
    {
        return $this->state(fn () => [
            'status' => ViolationStatus::Dismissed,
            'resolved_at' => now(),
        ]);
    }
}
