<?php

namespace Database\Factories;

use App\Enums\ThreadsPostStatus;
use App\Enums\ThreadsPostType;
use App\Models\ThreadsPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ThreadsPost>
 */
class ThreadsPostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => ThreadsPostType::Engagement,
            'status' => ThreadsPostStatus::Scheduled,
            'text' => fake()->sentence(12),
            'scheduled_at' => now()->addHour(),
            'attempts' => 0,
        ];
    }

    public function due(): static
    {
        return $this->state(fn (): array => ['scheduled_at' => now()->subMinute()]);
    }

    /** Threads has the container; the next run checks on it. */
    public function processing(): static
    {
        return $this->state(fn (): array => [
            'status' => ThreadsPostStatus::Processing,
            'scheduled_at' => now()->subMinutes(2),
            'container_id' => '17890000000000001',
            'container_created_at' => now()->subMinute(),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => ThreadsPostStatus::Published,
            'scheduled_at' => now()->subHour(),
            'threads_media_id' => '17890000000000002',
            'permalink' => 'https://www.threads.com/@neekah.my/post/abc',
            'published_at' => now()->subHour(),
        ]);
    }
}
