<?php

namespace Database\Factories;

use App\Enums\ThreadsPostType;
use App\Models\ThreadsTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ThreadsTemplate>
 */
class ThreadsTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => ThreadsPostType::Tip,
            'body' => fake()->sentence(15),
            'poll_options' => null,
            'topic_tag' => null,
            'is_active' => true,
            'used_count' => 0,
            'last_used_at' => null,
        ];
    }

    public function poll(): static
    {
        return $this->state(fn (): array => [
            'type' => ThreadsPostType::Engagement,
            'body' => 'Majlis impian anda: siang atau malam?',
            'poll_options' => ['Siang', 'Malam'],
        ]);
    }

    public function listing(): static
    {
        return $this->state(fn (): array => [
            'type' => ThreadsPostType::Listing,
            'body' => "{nama} · {kategori} · {negeri}\n{tagline}\n{pautan}",
        ]);
    }
}
