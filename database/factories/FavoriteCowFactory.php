<?php

namespace Database\Factories;

use App\Models\FavoriteCow;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FavoriteCow>
 */
class FavoriteCowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'animal_id' => fake()->unique()->numberBetween(1, 100000),
        ];
    }
}
