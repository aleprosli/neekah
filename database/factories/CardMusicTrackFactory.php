<?php

namespace Database\Factories;

use App\Models\CardMusicTrack;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CardMusicTrack>
 */
class CardMusicTrackFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->words(3, true);

        return [
            'title' => Str::title($title),
            'artist' => fake()->name(),
            'path' => 'card-music/'.Str::slug($title).'.mp3',
            'seconds' => fake()->numberBetween(90, 300),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
