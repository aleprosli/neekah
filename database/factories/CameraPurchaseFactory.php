<?php

namespace Database\Factories;

use App\Enums\CameraTier;
use App\Enums\SubscriptionStatus;
use App\Models\CameraPurchase;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CameraPurchase>
 */
class CameraPurchaseFactory extends Factory
{
    /**
     * A Basic checkout waiting for Herepay.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'reference' => CameraPurchase::generateReference(),
            'tier' => CameraTier::Basic,
            'kind' => CameraPurchase::KIND_NEW,
            'amount' => 29,
            'status' => SubscriptionStatus::Pending,
            'gateway' => CameraPurchase::GATEWAY_HEREPAY,
        ];
    }

    public function pro(): static
    {
        return $this->state(fn (): array => ['tier' => CameraTier::Pro, 'amount' => 99]);
    }
}
