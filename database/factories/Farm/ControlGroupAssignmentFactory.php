<?php

namespace Database\Factories\Farm;

use App\Models\Farm\ControlGroupAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ControlGroupAssignment>
 */
class ControlGroupAssignmentFactory extends Factory
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
            'experiment_id' => ControlGroupAssignment::FEED_ADJUSTMENT,
            'active' => true,
            'belongs_to_test_group' => true,
            'delta_feed' => fake()->randomElement([-1.0, -0.5, 0.0, 0.5, 1.0]),
        ];
    }
}
