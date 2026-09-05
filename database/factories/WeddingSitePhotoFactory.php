<?php

namespace Database\Factories;

use App\Models\WeddingSite;
use App\Models\WeddingSitePhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeddingSitePhoto>
 */
class WeddingSitePhotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wedding_site_id' => WeddingSite::factory(),
            'path' => 'sites/demo/'.fake()->uuid().'.jpg',
            'caption' => null,
            'sort_order' => 0,
        ];
    }
}
