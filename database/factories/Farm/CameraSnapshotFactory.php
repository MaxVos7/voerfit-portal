<?php

namespace Database\Factories\Farm;

use App\Models\Farm\CameraSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CameraSnapshot>
 */
class CameraSnapshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'animal_id' => fake()->numberBetween(1, 100000),
            'time' => now()->subDay()->toDateTimeString(),
            'gcs_path' => 'identified_snapshots/'.fake()->unique()->uuid().'_before.jpg',
            'label' => 'before',
        ];
    }
}
