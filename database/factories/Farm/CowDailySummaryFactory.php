<?php

namespace Database\Factories\Farm;

use App\Models\Farm\AnimalSummary;
use App\Models\Farm\CowDailySummary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CowDailySummary>
 */
class CowDailySummaryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'time' => now()->subDay()->toDateString(),
            'animal_id' => fake()->unique()->numberBetween(1, 100000),
            'life_number' => 'NL '.fake()->unique()->numerify('#########'),
            'days_in_milk' => fake()->numberBetween(1, 400),
            'lactation_number' => fake()->numberBetween(1, 8),
            'is_lactating' => true,
            'interval_milk_production' => fake()->randomFloat(1, 10, 50),
            'bcs' => null,
            'feed_total_sum_n1' => null,
            'feed_total_sum_n3' => null,
            'feed_total_original_sum_n1' => null,
            'feed_total_original_sum_n3' => null,
        ];
    }

    public function onDay(string $day): static
    {
        return $this->state(fn (array $attributes) => ['time' => $day]);
    }

    public function forAnimal(AnimalSummary $animal): static
    {
        return $this->state(fn (array $attributes) => [
            'animal_id' => $animal->animal_id,
            'life_number' => $animal->life_number,
        ]);
    }

    public function dry(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_lactating' => false,
            'days_in_milk' => null,
        ]);
    }
}
