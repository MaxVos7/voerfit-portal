<?php

use App\Models\Farm\CowDailySummary;
use App\Models\Farm\IngestAnimal;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    createFarmSchema();
    $this->actingAs(User::factory()->create());
    $this->travelTo('2026-10-20 09:00:00');
});

it('renders the lactating cows of the selected day', function () {
    CowDailySummary::factory()->onDay('2026-10-19')->create(['animal_id' => 1]);
    CowDailySummary::factory()->onDay('2026-10-19')->dry()->create(['animal_id' => 2]);
    CowDailySummary::factory()->onDay('2026-10-18')->create(['animal_id' => 3]);

    $this->get(route('day-overview', ['dag' => '2026-10-19']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('farm/DayOverview')
            ->where('selectedDay', '2026-10-19')
            ->has('cows', 1)
            ->where('cows.0.animalId', 1)
        );
});

it('defaults to the most recent day before today', function () {
    CowDailySummary::factory()->onDay('2026-10-20')->create(['animal_id' => 1]);
    CowDailySummary::factory()->onDay('2026-10-19')->create(['animal_id' => 2]);
    CowDailySummary::factory()->onDay('2026-10-18')->create(['animal_id' => 3]);

    $this->get(route('day-overview'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedDay', '2026-10-19')
            ->where('cows.0.animalId', 2)
        );
});

it('falls back to the most recent day when the day is not a valid date', function (string $day) {
    CowDailySummary::factory()->onDay('2026-10-19')->create();

    $this->get(route('day-overview', ['dag' => $day]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('selectedDay', '2026-10-19'));
})->with([
    'not a date' => 'gisteren',
    'impossible date' => '2026-13-45',
    'wrong format' => '19-10-2026',
]);

it('lists the days of the last 400 days before today newest first with Dutch labels', function () {
    CowDailySummary::factory()->onDay('2026-10-20')->create(['animal_id' => 1]);
    CowDailySummary::factory()->onDay('2026-10-19')->create(['animal_id' => 1]);
    CowDailySummary::factory()->onDay('2026-10-19')->create(['animal_id' => 2]);
    CowDailySummary::factory()->onDay('2025-12-03')->create(['animal_id' => 1]);
    CowDailySummary::factory()->onDay('2025-09-15')->create(['animal_id' => 1]);

    $this->get(route('day-overview'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('days', [
                ['value' => '2026-10-19', 'label' => '19 oktober'],
                ['value' => '2025-12-03', 'label' => '3 december 2025'],
            ])
        );
});

it('shows the cow number matched on life number regardless of spaces and case', function () {
    IngestAnimal::factory()->create(['life_number' => 'nl 608892168', 'user_number' => 477]);
    CowDailySummary::factory()->onDay('2026-10-19')->create(['life_number' => 'NL608892168']);

    $this->get(route('day-overview', ['dag' => '2026-10-19']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('cows.0.cowNumber', 477)
            ->where('cows.0.lifeNumber', 'NL608892168')
        );
});

it('sorts the cows by cow number with unknown numbers last', function () {
    IngestAnimal::factory()->create(['life_number' => 'NL 2', 'user_number' => 501]);
    IngestAnimal::factory()->create(['life_number' => 'NL 3', 'user_number' => 169]);
    CowDailySummary::factory()->onDay('2026-10-19')->create(['animal_id' => 1, 'life_number' => 'NL 1']);
    CowDailySummary::factory()->onDay('2026-10-19')->create(['animal_id' => 2, 'life_number' => 'NL 2']);
    CowDailySummary::factory()->onDay('2026-10-19')->create(['animal_id' => 3, 'life_number' => 'NL 3']);

    $this->get(route('day-overview', ['dag' => '2026-10-19']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('cows.0.cowNumber', 169)
            ->where('cows.1.cowNumber', 501)
            ->where('cows.2.cowNumber', null)
        );
});

// Props travel as JSON, so whole numbers arrive as integers (5.0 becomes 5).
it('shows the feed adjustment as applied minus original amount', function (int|float|null $applied, int|float|null $original, int|float|null $delta) {
    CowDailySummary::factory()->onDay('2026-10-19')->create([
        'feed_total_sum_n1' => $applied,
        'feed_total_original_sum_n1' => $original,
        'feed_total_sum_n3' => $applied,
        'feed_total_original_sum_n3' => $original,
    ]);

    $this->get(route('day-overview', ['dag' => '2026-10-19']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('cows.0.feedN1', $applied)
            ->where('cows.0.feedN1Delta', $delta)
            ->where('cows.0.feedN3', $applied)
            ->where('cows.0.feedN3Delta', $delta)
        );
})->with([
    'increased' => [3.7, 2.7, 1],
    'decreased' => [4.4, 6.9, -2.5],
    'no original recorded' => [5, null, null],
    'difference rounds to zero' => [5, 5.004, null],
    'no feed recorded' => [null, 4, null],
]);

it('uses the latest BCS on or before the selected day', function () {
    CowDailySummary::factory()->onDay('2026-10-10')->create(['animal_id' => 1, 'bcs' => 3.25]);
    CowDailySummary::factory()->onDay('2026-10-19')->create(['animal_id' => 1, 'bcs' => null]);
    CowDailySummary::factory()->onDay('2026-10-20')->create(['animal_id' => 1, 'bcs' => 3.9]);

    $this->get(route('day-overview', ['dag' => '2026-10-19']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('cows.0.bcs', 3.25)
            ->where('cows.0.bcsAgeDays', 9)
        );
});

it('flags the BCS as outdated when it is older than 15 days', function (string $measuredOn, int $ageDays, bool $isOutdated) {
    CowDailySummary::factory()->onDay($measuredOn)->create(['animal_id' => 1, 'bcs' => 3.5]);
    CowDailySummary::factory()->onDay('2026-10-19')->create(['animal_id' => 1, 'bcs' => null]);

    $this->get(route('day-overview', ['dag' => '2026-10-19']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('cows.0.bcsAgeDays', $ageDays)
            ->where('cows.0.bcsIsOutdated', $isOutdated)
        );
})->with([
    '15 days old' => ['2026-10-04', 15, false],
    '16 days old' => ['2026-10-03', 16, true],
]);

it('ignores BCS readings older than a year', function () {
    CowDailySummary::factory()->onDay('2025-10-18')->create(['animal_id' => 1, 'bcs' => 3.5]);
    CowDailySummary::factory()->onDay('2026-10-19')->create(['animal_id' => 1, 'bcs' => null]);

    $this->get(route('day-overview', ['dag' => '2026-10-19']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('cows.0.bcs', null)
            ->where('cows.0.bcsAgeDays', null)
        );
});

it('returns no BCS when the cow was never scored', function () {
    CowDailySummary::factory()->onDay('2026-10-19')->create(['bcs' => null]);

    $this->get(route('day-overview', ['dag' => '2026-10-19']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('cows.0.bcs', null)
            ->where('cows.0.bcsAgeDays', null)
            ->where('cows.0.bcsIsOutdated', false)
        );
});

it('calculates meetmelk as fat and protein corrected milk', function () {
    CowDailySummary::factory()->onDay('2026-10-19')->create([
        'lab_milk_quantity_mass' => 20.0,
        'lab_fat_percentage' => 5.05,
        'lab_protein_percentage' => 4.0,
    ]);

    $this->get(route('day-overview', ['dag' => '2026-10-19']))
        ->assertInertia(fn (Assert $page) => $page
            // (0.337 + 0.116 * 5.05 + 0.06 * 4.0) * 20.0 = 23.256, rounded to 2 decimals
            ->where('cows.0.fpcm', 23.26)
        );
});

it('returns no meetmelk when the milk sample is incomplete', function () {
    CowDailySummary::factory()->onDay('2026-10-19')->create([
        'lab_milk_quantity_mass' => 20.0,
        'lab_fat_percentage' => null,
        'lab_protein_percentage' => 4.0,
    ]);

    $this->get(route('day-overview', ['dag' => '2026-10-19']))
        ->assertInertia(fn (Assert $page) => $page->where('cows.0.fpcm', null));
});

it('returns the milk sample values of the cow', function () {
    CowDailySummary::factory()->onDay('2026-10-19')->create([
        'lab_fat_percentage' => 5.05,
        'lab_protein_percentage' => 4.0,
        'lab_lactose_percentage' => 4.47,
        'lab_ureum' => 17.0,
        'lab_cell_number' => 76,
        'lab_sample_date' => '2026-09-25',
    ]);

    $this->get(route('day-overview', ['dag' => '2026-10-19']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('cows.0.fat', 5.05)
            ->where('cows.0.protein', 4)
            ->where('cows.0.lactose', 4.47)
            ->where('cows.0.urea', 17)
            ->where('cows.0.cellCount', 76)
            ->where('cows.0.sampleDate', '2026-09-25')
        );
});

it('averages milk production fat protein urea and BCS over the lactating cows', function () {
    CowDailySummary::factory()->onDay('2026-10-19')->create([
        'animal_id' => 1, 'interval_milk_production' => 40.0, 'lab_fat_percentage' => 4.0,
        'lab_protein_percentage' => 3.0, 'lab_ureum' => 20.0, 'bcs' => 3.0,
    ]);
    CowDailySummary::factory()->onDay('2026-10-19')->create([
        'animal_id' => 2, 'interval_milk_production' => 30.0, 'lab_fat_percentage' => 5.0,
        'lab_protein_percentage' => 4.0, 'lab_ureum' => 10.0, 'bcs' => null,
    ]);
    CowDailySummary::factory()->onDay('2026-10-19')->dry()->create([
        'animal_id' => 3, 'interval_milk_production' => 0.0, 'bcs' => 1.0,
    ]);

    $this->get(route('day-overview', ['dag' => '2026-10-19']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('averages', [
                'milkProduction' => 35,
                'fat' => 4.5,
                'protein' => 3.5,
                'urea' => 15,
                'bcs' => 3,
            ])
        );
});

it('returns empty data when there are no days yet', function () {
    $this->get(route('day-overview'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('days', [])
            ->where('selectedDay', null)
            ->where('cows', [])
        );
});
