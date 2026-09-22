<?php

namespace Database\Factories;

use App\Models\CardNfcCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CardNfcCard>
 */
class CardNfcCardFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uid' => CardNfcCard::freshUid(),
            'label' => 'Kad '.fake()->unique()->numberBetween(1, 999),
            'is_active' => true,
        ];
    }
}
