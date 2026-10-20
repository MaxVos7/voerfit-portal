<?php

use App\Models\Farm\AgrovisionAnimal;
use App\Models\Farm\AgrovisionMilkSample;
use App\Models\Farm\AnimalSummary;
use App\Models\Farm\CowDailySummary;
use App\Models\Farm\IngestAnimal;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    createFarmSchema();
    $this->travelTo('2026-10-20 09:00:00');
});

it('renders the cow with its cow number, life number and name', function () {
    $cow = AnimalSummary::factory()->create(['life_number' => 'NL 656185061', 'name' => 'WILLE OLLA']);
    IngestAnimal::factory()->create(['life_number' => 'NL656185061', 'user_number' => 301]);

    $this->get(route('cows.show', $cow->animal_id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('farm/CowOverview')
            ->where('cow', [
                'animalId' => $cow->animal_id,
                'cowNumber' => 301,
                'lifeNumber' => 'NL 656185061',
                'name' => 'WILLE OLLA',
                'label' => '301 - NL 656185061 - WILLE OLLA',
            ])
        );
});

it('returns 404 for an unknown cow', function () {
    $this->get(route('cows.show', 999))->assertNotFound();
});

it('lists every cow for the cow picker by cow number with unknown numbers last', function () {
    $unnumbered = AnimalSummary::factory()->create(['life_number' => 'NL 3', 'name' => null]);
    $second = AnimalSummary::factory()->create(['life_number' => 'NL 2', 'name' => 'BELLA']);
    $first = AnimalSummary::factory()->create(['life_number' => 'NL 1', 'name' => '']);
    IngestAnimal::factory()->create(['life_number' => 'NL 1', 'user_number' => 12]);
    IngestAnimal::factory()->create(['life_number' => 'NL 2', 'user_number' => 300]);

    $this->get(route('cows.show', $first->animal_id))
        ->assertInertia(fn (Assert $page) => $page
            ->where('cows', [
                ['animalId' => $first->animal_id, 'label' => '12 - NL 1'],
                ['animalId' => $second->animal_id, 'label' => '300 - NL 2 - BELLA'],
                ['animalId' => $unnumbered->animal_id, 'label' => 'NL 3'],
            ])
        );
});

it('lists the lactations of the cow newest first with their date range', function () {
    $cow = AnimalSummary::factory()->create();
    CowDailySummary::factory()->forAnimal($cow)->onDay('2025-04-24')->create(['lactation_number' => 9]);
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-10-19')->create(['lactation_number' => 9]);
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-10-20')->create(['lactation_number' => 10]);
    CowDailySummary::factory()->forAnimal($cow)->onDay('2024-03-01')->create(['lactation_number' => 8]);
    CowDailySummary::factory()->forAnimal($cow)->onDay('2025-02-10')->create(['lactation_number' => 8]);

    $this->get(route('cows.show', $cow->animal_id))
        ->assertInertia(fn (Assert $page) => $page
            ->where('lactations', [
                ['value' => '9', 'label' => 'Lactatie 9 (24-04-2025 t/m 19-10-2026)'],
                ['value' => '8', 'label' => 'Lactatie 8 (01-03-2024 t/m 10-02-2025)'],
                ['value' => 'laatste-400-dagen', 'label' => 'Laatste 400 dagen'],
            ])
            ->where('selectedLactation', '9')
        );
});

it('falls back to the latest lactation when the requested one is unknown', function (string $lactation) {
    $cow = AnimalSummary::factory()->create();
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-10-19')->create(['lactation_number' => 9]);

    $this->get(route('cows.show', [$cow->animal_id, 'lactatie' => $lactation]))
        ->assertInertia(fn (Assert $page) => $page->where('selectedLactation', '9'));
})->with([
    'another cow\'s lactation' => '4',
    'not a number' => 'negen',
]);

it('falls back to the last 400 days when the cow has no lactations', function () {
    $cow = AnimalSummary::factory()->create();

    $this->get(route('cows.show', $cow->animal_id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedLactation', 'laatste-400-dagen')
            ->where('chart.days', [])
            ->where('summary', null)
        );
});

it('charts the days of the selected lactation up to yesterday', function () {
    $cow = AnimalSummary::factory()->create();
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-10-14')->create(['lactation_number' => 8]);
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-10-15')->create(['lactation_number' => 9]);
    // a day without a lactation number inside the range must not punch a gap
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-10-16')->create(['lactation_number' => null]);
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-10-19')->create(['lactation_number' => 9]);
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-10-20')->create(['lactation_number' => 9]);

    $this->get(route('cows.show', [$cow->animal_id, 'lactatie' => 9]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('chart.days', 3)
            ->where('chart.days.0.date', '2026-10-15')
            ->where('chart.days.1.date', '2026-10-16')
            ->where('chart.days.2.date', '2026-10-19')
        );
});

it('charts the last 400 days up to yesterday when no lactation is chosen', function () {
    $cow = AnimalSummary::factory()->create();
    // today minus 400 days is 2025-09-15, which is still included
    CowDailySummary::factory()->forAnimal($cow)->onDay('2025-09-14')->create(['lactation_number' => 8]);
    CowDailySummary::factory()->forAnimal($cow)->onDay('2025-09-15')->create(['lactation_number' => 8]);
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-10-19')->create(['lactation_number' => 9]);
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-10-20')->create(['lactation_number' => 9]);

    $this->get(route('cows.show', [$cow->animal_id, 'lactatie' => 'laatste-400-dagen']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('chart.days', 2)
            ->where('chart.days.0.date', '2025-09-15')
            ->where('chart.days.1.date', '2026-10-19')
        );
});

it('charts milk production, feed, rest feed, milkings and refusals per day', function () {
    $cow = AnimalSummary::factory()->create();
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-10-19')->create([
        'lactation_number' => 9,
        'days_in_milk' => 543,
        'interval_milk_production' => 34.5,
        'feed_total_sum_n1' => 3.7,
        'feed_total_original_sum_n1' => 2.7,
        'feed_total_sum_n3' => 0.8,
        'feed_total_original_sum_n3' => null,
        'feed_rest_last_n1' => 250,
        'feed_rest_last_n3' => null,
        'mdp_milkings' => 3,
        'mdp_refusals' => 1,
    ]);

    $this->get(route('cows.show', $cow->animal_id))
        ->assertInertia(fn (Assert $page) => $page
            ->where('chart.days.0', [
                'date' => '2026-10-19',
                'daysInMilk' => 543,
                'milkProduction' => 34.5,
                'feedN1' => 3.7,
                'feedN1Original' => 2.7,
                'feedN3' => 0.8,
                // without a recorded original the applied amount is the original
                'feedN3Original' => 0.8,
                // rest feed is stored in grams
                'restFeedN1' => 0.25,
                'restFeedN3' => 0,
                'milkings' => 3,
                'refusals' => 1,
            ])
        );
});

it('charts the BCS as one mean per fortnight on the last measured day in it', function () {
    $cow = AnimalSummary::factory()->create();
    // fortnights start on the Mondays 2026-09-28 and 2026-10-12, as in Grafana
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-10-05')->create(['lactation_number' => 9, 'bcs' => 3.0, 'bcs_sem' => 0.1]);
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-10-09')->create(['lactation_number' => 9, 'bcs' => 3.4, 'bcs_sem' => 0.3]);
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-10-10')->create(['lactation_number' => 9, 'bcs' => null]);
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-10-13')->create(['lactation_number' => 9, 'bcs' => 3.6, 'bcs_sem' => null]);

    $this->get(route('cows.show', $cow->animal_id))
        ->assertInertia(fn (Assert $page) => $page
            ->where('chart.bcs', [
                ['date' => '2026-10-09', 'bcs' => 3.2, 'sem' => 0.2],
                ['date' => '2026-10-13', 'bcs' => 3.6, 'sem' => null],
            ])
        );
});

it('charts the milk samples of the cow within the period', function () {
    $cow = AnimalSummary::factory()->create(['life_number' => 'NL 656185061']);
    $agrovisionAnimal = AgrovisionAnimal::factory()->create(['registration_id' => 'NL 656185061']);
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-09-01')->create(['lactation_number' => 9]);
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-10-19')->create(['lactation_number' => 9]);
    AgrovisionMilkSample::factory()->for($agrovisionAnimal, 'animal')->create([
        'date' => '2026-09-25', 'milk_quantity_mass' => 20.0, 'fat_percentage' => 5.05,
        'protein_percentage' => 4.0, 'lactose_percentage' => 4.47, 'urea' => 17.0, 'cell_value' => 76.0,
    ]);
    AgrovisionMilkSample::factory()->for($agrovisionAnimal, 'animal')->create(['date' => '2026-08-31']);
    AgrovisionMilkSample::factory()->create(['date' => '2026-09-26']);

    $this->get(route('cows.show', $cow->animal_id))
        ->assertInertia(fn (Assert $page) => $page
            ->where('chart.milkSamples', [[
                'date' => '2026-09-25',
                'milk' => 20,
                'fpcm' => 23.26,
                'fat' => 5.05,
                'protein' => 4,
                'lactose' => 4.47,
                'urea' => 17,
                'cellCount' => 76,
            ]])
        );
});

it('summarises the latest day of the lactation with milk production', function () {
    $cow = AnimalSummary::factory()->create();
    CowDailySummary::factory()->forAnimal($cow)->onDay('2025-04-24')->create(['lactation_number' => 9]);
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-10-18')->create([
        'lactation_number' => 9,
        'days_in_milk' => 542,
        'interval_milk_production' => 4.4,
        'lab_fat_percentage' => 3.99,
        'lab_protein_percentage' => 3.7,
        'lab_ureum' => 14.0,
        'feed_total_sum_n1' => 3.7,
        'feed_total_original_sum_n1' => 2.7,
        'feed_total_sum_n3' => 0.0,
        'feed_total_original_sum_n3' => 0.0,
    ]);
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-10-19')->create([
        'lactation_number' => 9, 'interval_milk_production' => null,
    ]);

    $this->get(route('cows.show', $cow->animal_id))
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary', [
                'date' => '2026-10-18',
                'dateLabel' => '18 oktober',
                'daysInMilk' => 542,
                'lactationNumber' => 9,
                'milkProduction' => 4.4,
                'fat' => 3.99,
                'protein' => 3.7,
                'urea' => 14,
                'feedN1' => 3.7,
                'feedN1Delta' => 1,
                'feedN3' => 0,
                'feedN3Delta' => null,
            ])
        );
});

it('derives the days in milk when the day has none recorded', function () {
    $cow = AnimalSummary::factory()->create();
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-06-01')->create(['lactation_number' => 9]);
    // days_in_milk is NULL on the dry-off day, the last day of a lactation
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-06-11')->create([
        'lactation_number' => 9, 'days_in_milk' => null, 'interval_milk_production' => 12.0,
    ]);

    $this->get(route('cows.show', $cow->animal_id))
        ->assertInertia(fn (Assert $page) => $page->where('summary.daysInMilk', 10));
});

it('summarises the latest day with milk production when no lactation is chosen', function () {
    $cow = AnimalSummary::factory()->create();
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-10-18')->create(['lactation_number' => 9, 'interval_milk_production' => 30.0]);
    CowDailySummary::factory()->forAnimal($cow)->onDay('2026-10-19')->create(['lactation_number' => null, 'interval_milk_production' => null]);

    $this->get(route('cows.show', [$cow->animal_id, 'lactatie' => 'laatste-400-dagen']))
        ->assertInertia(fn (Assert $page) => $page->where('summary.date', '2026-10-18'));
});
