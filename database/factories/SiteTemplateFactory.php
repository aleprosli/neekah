<?php

namespace Database\Factories;

use App\Models\SiteTemplate;
use App\Support\Card\Catalog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SiteTemplate>
 */
class SiteTemplateFactory extends Factory
{
    /**
     * A real composed design, because a card cannot render from made-up layers.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        $definition = fake()->randomElement(Catalog::all());

        return [
            ...SiteTemplate::attributesFromDefinition($definition),
            'slug' => Str::slug($name),
            'name' => Str::title($name),
            'sort_order' => 0,
        ];
    }

    /**
     * One named design from the catalogue, exactly as the seeder makes it.
     */
    public function fromCatalog(string $slug): static
    {
        $definition = collect(Catalog::all())->firstWhere('slug', $slug)
            ?? throw new \InvalidArgumentException('Unknown card design '.$slug);

        return $this->state(fn (): array => ['slug' => $slug] + SiteTemplate::attributesFromDefinition($definition));
    }
}
