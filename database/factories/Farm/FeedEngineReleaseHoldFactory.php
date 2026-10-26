<?php

namespace Database\Factories\Farm;

use App\Models\Farm\FeedEngineReleaseHold;
use App\Models\Farm\IngestAnimal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeedEngineReleaseHold>
 */
class FeedEngineReleaseHoldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'animal_id' => IngestAnimal::factory(),
            'feed_number' => fake()->randomElement([1, 3]),
            'hold_kg' => fake()->randomFloat(1, 1, 5),
            'last_applied_kg' => fake()->randomFloat(1, 5, 8),
            'last_applied_date' => now()->subDay()->toDateString(),
            'active' => true,
        ];
    }

    public function released(): static
    {
        return $this->state(fn (array $attributes) => ['active' => false]);
    }
}
