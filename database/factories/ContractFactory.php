<?php

namespace Database\Factories;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A draft contract with the five standard sections filled in.
 *
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            // Never a number the vendor's own sequence (KT-1001 up) can hand out.
            'number' => 'KT-T'.fake()->unique()->numerify('#####'),
            'token' => fn () => Contract::freshToken(),
            'status' => ContractStatus::Draft,
            'client_name' => fake()->name(),
            'client_phone' => '+60123456789',
            'client_email' => fake()->safeEmail(),
            'event_date' => fake()->dateTimeBetween('+2 months', '+1 year')->format('Y-m-d'),
            'sections' => array_map(fn (string $key): array => [
                'key' => $key,
                'title' => ucfirst(str_replace('_', ' ', $key)),
                'body' => fake()->sentence(),
            ], Contract::SECTION_KEYS),
        ];
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => ContractStatus::Sent,
            'sent_at' => now(),
            'vendor_signatory' => 'Ahmad Studio',
        ]);
    }

    public function signed(): static
    {
        return $this->sent()->state(fn () => [
            'status' => ContractStatus::Signed,
            'signed_at' => now(),
            'signer_name' => 'Aina Hakim',
            'signer_ip' => '127.0.0.1',
            'content_hash' => str_repeat('a', 64),
        ]);
    }

    public function void(): static
    {
        return $this->sent()->state(fn () => [
            'status' => ContractStatus::Void,
            'voided_at' => now(),
            'void_reason' => 'Tarikh berubah',
        ]);
    }
}
