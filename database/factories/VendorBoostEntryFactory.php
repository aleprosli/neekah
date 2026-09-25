<?php

namespace Database\Factories;

use App\Enums\BoostTokenReason;
use App\Models\Vendor;
use App\Models\VendorBoostEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorBoostEntry>
 */
class VendorBoostEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'change' => 5,
            'reason' => BoostTokenReason::Admin,
        ];
    }
}
