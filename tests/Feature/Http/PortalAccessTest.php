<?php

use App\Models\Farm\AnimalSummary;
use App\Models\User;

beforeEach(function () {
    createFarmSchema();
    AnimalSummary::factory()->create(['animal_id' => 5]);
});

it('sends guests to the login page', function (string $routeName, array $parameters) {
    $this->get(route($routeName, $parameters))->assertRedirect(route('login'));
})->with([
    'day overview' => ['day-overview', []],
    'cow overview' => ['cows.show', [5]],
    'camera images' => ['cows.camera', [5]],
    'feed rules' => ['feed-rules', []],
]);

it('shows the farm pages to a logged in user', function (string $routeName, array $parameters) {
    $this->actingAs(User::factory()->create())
        ->get(route($routeName, $parameters))
        ->assertOk();
})->with([
    'day overview' => ['day-overview', []],
    'cow overview' => ['cows.show', [5]],
    'camera images' => ['cows.camera', [5]],
    'feed rules' => ['feed-rules', []],
]);

it('does not offer registration', function () {
    $this->get('/register')->assertNotFound();
});
