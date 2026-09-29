<?php

namespace Database\Factories;

use App\Enums\CameraWishType;
use App\Models\CameraAlbum;
use App\Models\CameraWish;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CameraWish>
 */
class CameraWishFactory extends Factory
{
    /**
     * A written wish from a named guest.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'camera_album_id' => CameraAlbum::factory(),
            'type' => CameraWishType::Text,
            'message' => fake()->sentence(12),
            'guest_name' => fake()->firstName(),
            'device_hash' => hash('sha256', fake()->uuid()),
        ];
    }

    public function voice(): static
    {
        return $this->state(fn (): array => [
            'type' => CameraWishType::Voice,
            'message' => null,
            'audio_path' => 'camera/wishes/'.fake()->uuid().'.webm',
            'mime' => 'audio/webm',
            'bytes' => 48_000,
            'duration_seconds' => 12,
        ]);
    }
}
