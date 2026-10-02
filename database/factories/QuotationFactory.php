<?php

namespace Database\Factories;

use App\Enums\DepositType;
use App\Enums\QuotationItemKind;
use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A draft quotation with one RM3,000 line and a 30% deposit, priced.
 *
 * @extends Factory<Quotation>
 */
class QuotationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            // Never a number the vendor's own sequence (QT-1001 up) can hand out.
            'number' => 'QT-T'.fake()->unique()->numerify('#####'),
            'token' => fn () => Quotation::freshToken(),
            'status' => QuotationStatus::Draft,
            'client_name' => fake()->name(),
            'client_phone' => '+60123456789',
            'client_email' => fake()->safeEmail(),
            'event_date' => fake()->dateTimeBetween('+2 months', '+1 year')->format('Y-m-d'),
            'valid_until' => now()->addDays(Quotation::DEFAULT_VALID_DAYS)->toDateString(),
            'discount_type' => DepositType::Fixed,
            'discount_value' => 0,
            'deposit_type' => DepositType::Percent,
            'deposit_value' => 30,
            'terms' => 'Deposit tidak dikembalikan.',
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Quotation $quotation): void {
            if ($quotation->items()->exists()) {
                return;
            }

            $quotation->items()->create([
                'kind' => QuotationItemKind::Addon,
                'name' => 'Pakej Fotografi',
                'quantity' => 1,
                'unit_price' => 3000,
                'line_total' => 3000,
            ]);
            $quotation->recalculate();
        });
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => QuotationStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'status' => QuotationStatus::Accepted,
            'sent_at' => now()->subDay(),
            'accepted_at' => now(),
            'accepted_name' => 'Aina Hakim',
            'accepted_ip' => '127.0.0.1',
        ]);
    }

    public function expired(): static
    {
        return $this->sent()->state(fn () => [
            'valid_until' => now()->subDay()->toDateString(),
        ]);
    }
}
