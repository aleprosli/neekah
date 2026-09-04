<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeddingInvitation>
 */
class WeddingInvitationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'invited_by' => User::factory(),
            'email' => fake()->unique()->safeEmail(),
            'token' => WeddingInvitation::generateToken(),
            'expires_at' => now()->addDays(WeddingInvitation::EXPIRES_AFTER_DAYS),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['accepted_at' => now(), 'accepted_by' => User::factory()]);
    }
}
