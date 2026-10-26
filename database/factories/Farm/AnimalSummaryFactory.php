<?php

namespace Database\Factories\Farm;

use App\Models\Farm\AnimalSummary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnimalSummary>
 */
class AnimalSummaryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'animal_id' => fake()->unique()->numberBetween(1, 100000),
            'life_number' => 'NL '.fake()->unique()->numerify('#########'),
            'name' => strtoupper(fake()->firstName()),
        ];
    }

    public function inHerd(int $herdId): static
    {
        return $this->state(fn (array $attributes) => ['herd_id' => $herdId]);
    }
}
