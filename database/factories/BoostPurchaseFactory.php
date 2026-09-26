<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\BoostPurchase;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoostPurchase>
 */
class BoostPurchaseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'reference' => BoostPurchase::generateReference(),
            'pack' => 'small',
            'tokens' => 10,
            'amount' => 20,
            'status' => SubscriptionStatus::Pending,
            'gateway' => BoostPurchase::GATEWAY_HEREPAY,
        ];
    }
}
