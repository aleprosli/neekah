<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Enums\VendorPlan;
use App\Models\Vendor;
use App\Models\VendorSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VendorSubscription>
 */
class VendorSubscriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'reference' => 'PRO-'.Str::upper(Str::random(8)),
            'plan' => VendorPlan::Monthly,
            'amount' => 49,
            'status' => SubscriptionStatus::Pending,
            'gateway' => VendorSubscription::GATEWAY_HEREPAY,
        ];
    }

    public function yearly(): static
    {
        return $this->state(fn () => ['plan' => VendorPlan::Yearly, 'amount' => 490]);
    }
}
