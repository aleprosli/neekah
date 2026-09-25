<?php

namespace Database\Factories;

use App\Enums\CameraTier;
use App\Models\CameraAlbum;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CameraAlbum>
 */
class CameraAlbumFactory extends Factory
{
    /**
     * An active Basic album, open for uploads until two weeks from now.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'token' => CameraAlbum::freshToken(),
            'tier' => CameraTier::Basic,
            'activated_at' => now(),
            'expires_at' => now()->addDays(14),
        ];
    }

    public function pro(): static
    {
        return $this->state(fn (): array => ['tier' => CameraTier::Pro]);
    }
}
