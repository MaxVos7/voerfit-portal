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

it('shows the before photos of every day below each other, newest day first, with Amsterdam times and public urls', function () {
    $cow = AnimalSummary::factory()->create(['animal_id' => 5]);
    CameraSnapshot::factory()->create([
        'animal_id' => 5, 'time' => '2026-10-19 07:30:00', 'gcs_path' => 'identified_snapshots/a_before.jpg',
    ]);
    CameraSnapshot::factory()->create([
        'animal_id' => 5, 'time' => '2026-10-19 16:10:00', 'gcs_path' => 'identified_snapshots/b_before.jpg',
    ]);
    CameraSnapshot::factory()->create([
        'animal_id' => 5, 'time' => '2026-10-17 10:00:00', 'gcs_path' => 'identified_snapshots/c_before.jpg',
    ]);
    CameraSnapshot::factory()->create([
        'animal_id' => 5, 'time' => '2026-10-19 07:35:00', 'gcs_path' => 'identified_snapshots/a_after.jpg', 'label' => 'after',
    ]);
    CameraSnapshot::factory()->create(['animal_id' => 6, 'time' => '2026-10-19 07:40:00']);

    $this->get(route('cows.camera', $cow->animal_id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('farm/CameraImages')
            ->where('days', [
                ['value' => '2026-10-19', 'label' => '19 oktober', 'photos' => [
                    ['url' => 'https://storage.googleapis.com/farm-camera-snapshots/identified_snapshots/a_before.jpg', 'time' => '09:30'],
                    ['url' => 'https://storage.googleapis.com/farm-camera-snapshots/identified_snapshots/b_before.jpg', 'time' => '18:10'],
                ]],
                ['value' => '2026-10-17', 'label' => '17 oktober', 'photos' => [
                    ['url' => 'https://storage.googleapis.com/farm-camera-snapshots/identified_snapshots/c_before.jpg', 'time' => '12:00'],
                ]],
            ])
        );
});

it('groups photos by Amsterdam day rather than UTC day', function () {
    AnimalSummary::factory()->create(['animal_id' => 5]);
    CameraSnapshot::factory()->create(['animal_id' => 5, 'time' => '2026-10-18 22:30:00']);

    $this->get(route('cows.camera', 5))
        ->assertInertia(fn (Assert $page) => $page
            ->has('days', 1)
            ->where('days.0.value', '2026-10-19')
            ->where('days.0.photos.0.time', '00:30')
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

it('shows the cow figures of the most recent day with photos', function () {
    $animal = IngestAnimal::factory()->create(['user_number' => 301]);
    AnimalSummary::factory()->create(['animal_id' => $animal->id, 'life_number' => $animal->life_number]);
    CameraSnapshot::factory()->create(['animal_id' => $animal->id, 'time' => '2026-10-19 07:30:00']);
    CameraSnapshot::factory()->create(['animal_id' => $animal->id, 'time' => '2026-10-15 07:30:00']);
    CowDailySummary::factory()->onDay('2026-10-19')->create([
        'animal_id' => $animal->id, 'life_number' => $animal->life_number, 'days_in_milk' => 150, 'interval_milk_production' => 34.4,
    ]);
    CowDailySummary::factory()->onDay('2026-10-15')->create([
        'animal_id' => $animal->id, 'life_number' => $animal->life_number, 'days_in_milk' => 146, 'interval_milk_production' => 30.0,
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
            ->where('summary', null)
        );
});

it('returns 404 for an unknown cow', function () {
    $this->get(route('cows.camera', 999))->assertNotFound();
});
