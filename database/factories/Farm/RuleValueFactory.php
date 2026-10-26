<?php

namespace Database\Factories\Farm;

use App\Models\Farm\RuleValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RuleValue>
 */
class RuleValueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
            'value' => (string) fake()->numberBetween(1, 20),
            'source' => 'feed-engine',
        ];
    }
}
