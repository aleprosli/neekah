<?php

namespace Database\Factories;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementStatus;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->admin(),
            'audience' => AnnouncementAudience::Everyone,
            'subject' => fake()->sentence(5),
            'body' => fake()->paragraphs(2, true),
            'action_label' => null,
            'action_url' => null,
            'status' => AnnouncementStatus::Draft,
            'recipients_count' => 0,
            'sent_at' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (): array => [
            'status' => AnnouncementStatus::Sent,
            'recipients_count' => fake()->numberBetween(1, 200),
            'sent_at' => now(),
        ]);
    }
}
