<?php

use App\Enums\DayOverviewColumn;
use App\Models\DashboardPreference;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    createFarmSchema();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function columnPayload(array $keys, array $hidden = []): array
{
    return array_map(fn (string $key): array => ['key' => $key, 'visible' => ! in_array($key, $hidden, true)], $keys);
}

function defaultColumns(): array
{
    return columnPayload(array_map(fn (DayOverviewColumn $column): string => $column->value, DayOverviewColumn::cases()));
}

it('shows every column in the default order when nothing is saved', function () {
    $this->get(route('day-overview'))
        ->assertInertia(fn (Assert $page) => $page->where('columns', defaultColumns()));
});

it('saves the order and visibility of the columns and shows them on the next visit', function () {
    $payload = columnPayload(['fat', 'cowNumber', 'milkProduction', 'protein', 'bcs'], hidden: ['bcs']);

    $this->put(route('day-overview.columns.update'), ['columns' => $payload])->assertRedirect();

    $columns = $this->get(route('day-overview'))->original->getData()['page']['props']['columns'];

    expect(array_slice($columns, 0, 5))->toBe($payload)
        ->and($columns)->toHaveCount(count(DayOverviewColumn::cases()))
        ->and(array_column(array_slice($columns, 5), 'visible'))->each->toBeTrue();
});

it('replaces the saved columns instead of adding a second record', function () {
    $this->put(route('day-overview.columns.update'), ['columns' => columnPayload(['fat', 'cowNumber'])]);
    $this->put(route('day-overview.columns.update'), ['columns' => columnPayload(['urea', 'cowNumber'])]);

    expect(DashboardPreference::where('user_id', $this->user->id)->count())->toBe(1);

    $this->get(route('day-overview'))
        ->assertInertia(fn (Assert $page) => $page->where('columns.0.key', 'urea'));
});

it('keeps the columns of each user apart', function () {
    $this->put(route('day-overview.columns.update'), ['columns' => columnPayload(['fat', 'cowNumber'])]);

    $this->actingAs(User::factory()->create())
        ->get(route('day-overview'))
        ->assertInertia(fn (Assert $page) => $page->where('columns', defaultColumns()));
});

it('adds columns that were not saved at the end as visible', function () {
    DashboardPreference::factory()->for($this->user)->withColumns(columnPayload(['fat', 'cowNumber']))->create();

    $this->get(route('day-overview'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('columns.0', ['key' => 'fat', 'visible' => true])
            ->where('columns.1', ['key' => 'cowNumber', 'visible' => true])
            ->has('columns', count(DayOverviewColumn::cases()))
            ->where('columns.2', ['key' => 'lifeNumber', 'visible' => true])
        );
});

it('ignores saved columns that no longer exist', function () {
    DashboardPreference::factory()->for($this->user)->withColumns([
        ['key' => 'retired', 'visible' => true],
        ['key' => 'fat', 'visible' => false],
        ['key' => 'fat', 'visible' => true],
    ])->create();

    $this->get(route('day-overview'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('columns', count(DayOverviewColumn::cases()))
            ->where('columns.0', ['key' => 'fat', 'visible' => false])
        );
});

it('restores the default columns', function () {
    DashboardPreference::factory()->for($this->user)->withColumns(columnPayload(['fat']))->create();

    $this->delete(route('day-overview.columns.destroy'))->assertRedirect();

    expect(DashboardPreference::count())->toBe(0);
    $this->get(route('day-overview'))
        ->assertInertia(fn (Assert $page) => $page->where('columns', defaultColumns()));
});

it('rejects invalid columns', function (array $payload) {
    $this->put(route('day-overview.columns.update'), $payload)->assertSessionHasErrors();

    expect(DashboardPreference::count())->toBe(0);
})->with([
    'nothing sent' => [[]],
    'empty list' => [['columns' => []]],
    'unknown column' => [['columns' => [['key' => 'secret', 'visible' => true]]]],
    'duplicate column' => [['columns' => columnPayload(['fat', 'fat'])]],
    'every column hidden' => [['columns' => columnPayload(['fat', 'urea'], hidden: ['fat', 'urea'])]],
    'visibility is not a boolean' => [['columns' => [['key' => 'fat', 'visible' => 'maybe']]]],
]);

it('sends guests to the login page', function () {
    auth()->logout();

    $this->put(route('day-overview.columns.update'), ['columns' => columnPayload(['fat'])])
        ->assertRedirect(route('login'));
    $this->delete(route('day-overview.columns.destroy'))->assertRedirect(route('login'));
});
