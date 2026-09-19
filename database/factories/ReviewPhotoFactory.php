<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\ReviewPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewPhoto>
 */
class ReviewPhotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'review_id' => Review::factory(),
            'path' => 'reviews/'.fake()->uuid().'.webp',
            'sort_order' => 0,
        ];
    }
}
