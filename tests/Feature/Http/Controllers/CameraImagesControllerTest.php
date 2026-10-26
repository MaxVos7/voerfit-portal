<?php

use App\Models\Farm\AnimalSummary;
use App\Models\Farm\CameraSnapshot;
use App\Models\Farm\CowDailySummary;
use App\Models\Farm\IngestAnimal;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    createFarmSchema();
    $this->actingAs(User::factory()->create());
    $this->travelTo('2026-10-20 09:00:00');
});

it('shows the before photos of the selected day with their Amsterdam time and public url', function () {
    $cow = AnimalSummary::factory()->create(['animal_id' => 5]);
    CameraSnapshot::factory()->create([
        'animal_id' => 5, 'time' => '2026-10-19 07:30:00', 'gcs_path' => 'identified_snapshots/a_before.jpg',
    ]);
    CameraSnapshot::factory()->create([
        'animal_id' => 5, 'time' => '2026-10-19 07:35:00', 'gcs_path' => 'identified_snapshots/a_after.jpg', 'label' => 'after',
    ]);
    CameraSnapshot::factory()->create(['animal_id' => 6, 'time' => '2026-10-19 07:40:00']);

    $this->get(route('cows.camera', ['animal' => $cow->animal_id, 'dag' => '2026-10-19']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('farm/CameraImages')
            ->where('selectedDay', '2026-10-19')
            ->where('photos', [[
                'url' => 'https://storage.googleapis.com/farm-camera-snapshots/identified_snapshots/a_before.jpg',
                'time' => '09:30',
            ]])
        );
});

it('lists the days that have photos, newest first, grouped by Amsterdam day', function () {
    AnimalSummary::factory()->create(['animal_id' => 5]);
    CameraSnapshot::factory()->create(['animal_id' => 5, 'time' => '2026-10-18 22:30:00']);
    CameraSnapshot::factory()->create(['animal_id' => 5, 'time' => '2026-10-17 10:00:00']);

    $this->get(route('cows.camera', 5))
        ->assertInertia(fn (Assert $page) => $page
            ->where('days', [
                ['value' => '2026-10-19', 'label' => '19 oktober'],
                ['value' => '2026-10-17', 'label' => '17 oktober'],
            ])
            ->where('selectedDay', '2026-10-19')
        );
});

it('only offers cows that have photos', function () {
    AnimalSummary::factory()->create(['animal_id' => 5, 'life_number' => 'NL 111']);
    AnimalSummary::factory()->create(['animal_id' => 6, 'life_number' => 'NL 222']);
    CameraSnapshot::factory()->create(['animal_id' => 5]);

    $this->get(route('cows.camera', 5))
        ->assertInertia(fn (Assert $page) => $page
            ->has('cows', 1)
            ->where('cows.0.animalId', 5)
        );
});

it('shows the cow figures of the selected day', function () {
    $animal = IngestAnimal::factory()->create(['user_number' => 301]);
    AnimalSummary::factory()->create(['animal_id' => $animal->id, 'life_number' => $animal->life_number]);
    CameraSnapshot::factory()->create(['animal_id' => $animal->id, 'time' => '2026-10-19 07:30:00']);
    CowDailySummary::factory()->onDay('2026-10-19')->create([
        'animal_id' => $animal->id, 'life_number' => $animal->life_number, 'days_in_milk' => 150, 'interval_milk_production' => 34.4,
    ]);

    $this->get(route('cows.camera', $animal->id))
        ->assertInertia(fn (Assert $page) => $page
            ->where('cow.cowNumber', 301)
            ->where('summary.daysInMilk', 150)
            ->where('summary.milkProduction', 34.4)
        );
});

it('renders without photos when the cow has none', function () {
    AnimalSummary::factory()->create(['animal_id' => 5]);

    $this->get(route('cows.camera', 5))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('days', [])
            ->where('selectedDay', null)
            ->where('photos', [])
            ->where('summary', null)
        );
});

it('returns 404 for an unknown cow', function () {
    $this->get(route('cows.camera', 999))->assertNotFound();
});
