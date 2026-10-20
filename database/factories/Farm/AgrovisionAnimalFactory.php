<?php

namespace Database\Factories\Farm;

use App\Models\Farm\AgrovisionAnimal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgrovisionAnimal>
 */
class AgrovisionAnimalFactory extends Factory
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
            'registration_id' => 'NL '.fake()->unique()->numerify('#########'),
        ];
    }
}
