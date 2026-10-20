<?php

namespace Database\Factories\Farm;

use App\Models\Farm\IngestAnimal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IngestAnimal>
 */
class IngestAnimalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'life_number' => 'NL '.fake()->unique()->numerify('#########'),
            'user_number' => fake()->unique()->numberBetween(1, 9999),
        ];
    }
}
