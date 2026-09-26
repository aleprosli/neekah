<?php

namespace Database\Factories;

use App\Enums\CameraMediaStatus;
use App\Enums\CameraMediaType;
use App\Models\CameraAlbum;
use App\Models\CameraMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CameraMedia>
 */
class CameraMediaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'camera_album_id' => CameraAlbum::factory(),
            'type' => CameraMediaType::Photo,
            'status' => CameraMediaStatus::Ready,
            'path' => 'camera/1/'.fake()->uuid().'.webp',
            'bytes' => 250_000,
            'mime' => 'image/webp',
            'uploader_name' => fake()->firstName(),
            'device_hash' => hash('sha256', fake()->uuid()),
        ];
    }
}
