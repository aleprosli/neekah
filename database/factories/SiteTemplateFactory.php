<?php

namespace Database\Factories;

use App\Models\SiteTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SiteTemplate>
 */
class SiteTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'slug' => Str::slug($name),
            'name' => Str::title($name),
            'style' => 'Klasik',
            'description' => fake()->sentence(6),
            'sort_order' => 0,
            'is_active' => true,
            'design' => [
                'layout' => fake()->randomElement(SiteTemplate::LAYOUTS),
                'ornament' => fake()->randomElement(SiteTemplate::ORNAMENTS),
                'motion' => 'petals',
                'eyebrow' => 'Walimatulurus',
                'type' => ['script' => "'Great Vibes', cursive", 'body' => "'Cormorant Garamond', serif"],
                'palette' => ['page' => '#ffffff', 'ink' => '#2b2b2b', 'name' => '#7a2230', 'accent' => '#c19a4b'],
                'petals' => ['#f2c6d4'],
            ],
        ];
    }
}
