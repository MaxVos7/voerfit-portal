<?php

use App\Models\Farm\AnimalSummary;
use App\Models\Farm\CameraSnapshot;
use App\Models\Farm\ControlGroupAssignment;
use App\Models\Farm\CowDailySummary;
use App\Models\Farm\FeedEngineReleaseHold;
use App\Models\Farm\IngestAnimal;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    createFarmSchema();
    $this->travelTo('2026-10-20 09:00:00');

    AnimalSummary::factory()->inHerd(1)->create(['animal_id' => 1]);
    AnimalSummary::factory()->inHerd(2)->create(['animal_id' => 2]);
    AnimalSummary::factory()->create(['animal_id' => 3]);
    foreach ([1, 2, 3] as $animalId) {
        CowDailySummary::factory()->onDay('2026-10-19')->create(['animal_id' => $animalId]);
        CameraSnapshot::factory()->create(['animal_id' => $animalId, 'time' => '2026-10-19 07:30:00']);
    }
});

it('limits the day overview to the cows of the user\'s herd', function () {
    $this->actingAs(User::factory()->forHerd(1)->create())
        ->get(route('day-overview', ['dag' => '2026-10-19']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('cows', 1)
            ->where('cows.0.animalId', 1)
        );
});

it('does not limit a user without a herd', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('day-overview', ['dag' => '2026-10-19']))
        ->assertInertia(fn (Assert $page) => $page->has('cows', 3));
});

it('returns 404 for a cow of another herd', function (string $routeName) {
    $this->actingAs(User::factory()->forHerd(1)->create())
        ->get(route($routeName, 2))
        ->assertNotFound();
})->with(['cow overview' => 'cows.show', 'camera images' => 'cows.camera']);

it('only offers the cows of the user\'s herd in the cow pickers', function () {
    $user = User::factory()->forHerd(1)->create();

    $this->actingAs($user)->get(route('cows.show', 1))
        ->assertInertia(fn (Assert $page) => $page->has('cows', 1)->where('cows.0.animalId', 1));

    $this->actingAs($user)->get(route('cows.camera', 1))
        ->assertInertia(fn (Assert $page) => $page->has('cows', 1)->where('cows.0.animalId', 1));
});

it('limits the feed rule tables to the cows of the user\'s herd', function () {
    $own = IngestAnimal::factory()->create(['id' => 101, 'user_number' => 11]);
    $other = IngestAnimal::factory()->create(['id' => 102, 'user_number' => 22]);
    AnimalSummary::factory()->inHerd(1)->create(['animal_id' => $own->id]);
    AnimalSummary::factory()->inHerd(2)->create(['animal_id' => $other->id]);
    FeedEngineReleaseHold::factory()->create(['animal_id' => $own->id]);
    FeedEngineReleaseHold::factory()->create(['animal_id' => $other->id]);
    ControlGroupAssignment::factory()->create(['animal_id' => $own->id, 'delta_feed' => 0.5]);
    ControlGroupAssignment::factory()->create(['animal_id' => $other->id, 'delta_feed' => 0.5]);

    $this->actingAs(User::factory()->forHerd(1)->create())
        ->get(route('feed-rules'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('holds', 1)
            ->where('holds.0.cowNumber', 11)
            ->where('distribution', [['delta' => 0.5, 'cows' => 1]])
        );
});
