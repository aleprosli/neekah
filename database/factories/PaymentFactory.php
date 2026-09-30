<?php

namespace Database\Factories;

use App\Enums\CameraTier;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\VendorPlan;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Vendor;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * A booking payment the couple recorded by hand, waiting for the vendor.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purpose' => PaymentPurpose::Booking,
            'booking_id' => Booking::factory(),
            'amount' => fake()->numberBetween(2, 20) * 100,
            'paid_on' => now()->toDateString(),
            'method' => 'manual_transfer',
            'status' => PaymentStatus::AwaitingVerification,
            'gateway' => Payment::GATEWAY_MANUAL,
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

    /** A checkout on a gateway, waiting for it to say. */
    public function herepay(): static
    {
        return $this->state(fn () => [
            'gateway' => Payment::GATEWAY_HEREPAY,
            'method' => null,
            'paid_on' => null,
            'status' => PaymentStatus::Pending,
        ]);
    }

    /** A Neekah Pro checkout for a vendor. */
    public function pro(VendorPlan $plan = VendorPlan::Monthly): static
    {
        return $this->herepay()->state(fn () => [
            'purpose' => PaymentPurpose::VendorPro,
            'booking_id' => null,
            'vendor_id' => Vendor::factory(),
            'amount' => $plan->price(),
            'details' => ['plan' => $plan->value],
        ]);
    }

    /** A boost pack checkout for a vendor. */
    public function boostPack(string $pack = 'small', int $tokens = 5, float $amount = 10): static
    {
        return $this->herepay()->state(fn () => [
            'purpose' => PaymentPurpose::BoostTokens,
            'booking_id' => null,
            'vendor_id' => Vendor::factory(),
            'amount' => $amount,
            'details' => ['pack' => $pack, 'tokens' => $tokens],
        ]);
    }

    /** A new Neekah Kenangan album checkout for a wedding. */
    public function kenangan(CameraTier $tier = CameraTier::Basic): static
    {
        return $this->herepay()->state(fn () => [
            'purpose' => PaymentPurpose::Kenangan,
            'booking_id' => null,
            'wedding_id' => Wedding::factory(),
            'amount' => $tier === CameraTier::Pro ? 99 : 29,
            'details' => ['tier' => $tier->value, 'kind' => 'new'],
        ]);
    }
}
