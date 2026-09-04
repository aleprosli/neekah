<?php

namespace Database\Factories;

use App\Enums\PriceUnit;
use App\Enums\VendorStatus;
use App\Enums\VendorTier;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company().' Weddings';

        return [
            'user_id' => User::factory()->vendor(),
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'tagline' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(Vendor::STATES),
            'phone' => fake()->numerify('01#-### ####'),
            'whatsapp' => fake()->numerify('601########'),
            'price_from' => fake()->numberBetween(5, 100) * 100,
            'price_unit' => PriceUnit::Package,
            'cover_tone' => fake()->randomElement(['from-rose-400 to-amber-300', 'from-amber-500 to-orange-300', 'from-fuchsia-400 to-rose-300', 'from-slate-700 to-slate-400', 'from-emerald-500 to-teal-300', 'from-sky-400 to-indigo-300']),
            'status' => VendorStatus::Approved,
            'tier' => VendorTier::Verified,
            'rating_avg' => 0,
            'reviews_count' => 0,
            'completed_bookings_count' => 0,
            'response_rate' => fake()->numberBetween(80, 100),
            'score' => 0,
            'approved_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => VendorStatus::Pending,
            'tier' => VendorTier::New,
            'approved_at' => null,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => VendorStatus::Suspended]);
    }

    public function tier(VendorTier $tier): static
    {
        return $this->state(fn () => ['tier' => $tier]);
    }

    public function perPax(): static
    {
        return $this->state(fn () => [
            'price_unit' => PriceUnit::Pax,
            'price_from' => fake()->numberBetween(15, 60),
        ]);
    }
}
