<?php

namespace Database\Factories;

use App\Enums\SongMoment;
use App\Models\Wedding;
use App\Models\WeddingSong;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeddingSong>
 */
class WeddingSongFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'moment' => fake()->randomElement(SongMoment::cases()),
            'title' => fake()->words(3, true),
            'artist' => fake()->name(),
        ];
    }
}
