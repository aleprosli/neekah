<?php

namespace Database\Factories;

use App\Enums\PointReason;
use App\Models\Vendor;
use App\Models\VendorPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorPoint>
 */
class VendorPointFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $reason = fake()->randomElement(PointReason::cases());

        return [
            'vendor_id' => Vendor::factory(),
            'reason' => $reason,
            'points' => $reason->points(),
        ];
    }
}
