<?php

namespace Database\Factories\Farm;

use App\Models\Farm\AgrovisionAnimal;
use App\Models\Farm\AgrovisionMilkSample;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgrovisionMilkSample>
 */
class AgrovisionMilkSampleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'animal_id' => AgrovisionAnimal::factory(),
            'date' => now()->subMonth()->toDateString(),
            'milk_quantity_mass' => fake()->randomFloat(1, 15, 50),
            'fat_percentage' => fake()->randomFloat(2, 3, 6),
            'protein_percentage' => fake()->randomFloat(2, 3, 4.5),
            'lactose_percentage' => fake()->randomFloat(2, 4, 5),
            'cell_value' => fake()->numberBetween(5, 500),
            'urea' => fake()->numberBetween(10, 35),
        ];
    }
}
