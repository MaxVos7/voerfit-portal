<?php

namespace Database\Factories;

use App\Models\DashboardPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DashboardPreference>
 */
class DashboardPreferenceFactory extends Factory
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
            'dashboard' => DashboardPreference::DAY_OVERVIEW,
            'settings' => [],
        ];
    }

    /**
     * Save the day overview columns as the user left them.
     *
     * @param  list<array{key: string, visible: bool}>  $columns
     */
    public function withColumns(array $columns): static
    {
        return $this->state(fn (array $attributes) => ['settings' => ['columns' => $columns]]);
    }
}
