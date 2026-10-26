<?php

use App\Models\Farm\ControlGroupAssignment;
use App\Models\Farm\FeedEngineReleaseHold;
use App\Models\Farm\IngestAnimal;
use App\Models\Farm\RuleValue;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    createFarmSchema();
    $this->actingAs(User::factory()->create());
});

it('passes every rule value to the page by name', function () {
    RuleValue::factory()->create(['name' => 'weekly_step', 'value' => '0,5']);
    RuleValue::factory()->create(['name' => 'engine_step', 'value' => '0,2']);

    $this->get(route('feed-rules'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('farm/FeedRules')
            ->where('rules', ['weekly_step' => '0,5', 'engine_step' => '0,2'])
        );
});

it('lists the active release holds with the cow number and leaves released ones out', function () {
    $cow = IngestAnimal::factory()->create(['user_number' => 301]);
    $otherCow = IngestAnimal::factory()->create(['user_number' => 120]);
    FeedEngineReleaseHold::factory()->create([
        'animal_id' => $cow->id, 'feed_number' => 3, 'last_applied_kg' => 4.2, 'hold_kg' => 3.0, 'last_applied_date' => '2026-10-24',
    ]);
    FeedEngineReleaseHold::factory()->create([
        'animal_id' => $otherCow->id, 'feed_number' => 1, 'last_applied_kg' => 6.0, 'hold_kg' => 5.0, 'last_applied_date' => '2026-10-25',
    ]);
    FeedEngineReleaseHold::factory()->released()->create(['animal_id' => $cow->id]);

    $this->get(route('feed-rules'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('holds', 2)
            ->where('holds.0', [
                'cowNumber' => 120, 'feedNumber' => 1, 'currentKg' => 6, 'targetKg' => 5, 'lastStepDate' => '2026-10-25',
            ])
            ->where('holds.1.cowNumber', 301)
        );
});

it('counts the trial cows per feed deviation for the active feed adjustment experiment only', function () {
    ControlGroupAssignment::factory()->count(2)->create(['delta_feed' => 0.5]);
    ControlGroupAssignment::factory()->create(['delta_feed' => -1.0]);
    ControlGroupAssignment::factory()->create(['delta_feed' => 0.5, 'active' => false]);
    ControlGroupAssignment::factory()->create(['delta_feed' => 0.5, 'belongs_to_test_group' => false]);
    ControlGroupAssignment::factory()->create(['delta_feed' => 0.5, 'experiment_id' => 'other']);

    $this->get(route('feed-rules'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('distribution', [
                ['delta' => -1, 'cows' => 1],
                ['delta' => 0.5, 'cows' => 2],
            ])
        );
});
