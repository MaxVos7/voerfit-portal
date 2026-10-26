<?php

use App\Models\Farm\AnimalSummary;
use App\Models\Farm\IngestAnimal;
use App\Models\FavoriteCow;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    createFarmSchema();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function createCow(int $animalId, int $cowNumber, ?string $name = null, ?int $herdId = null): AnimalSummary
{
    $animal = IngestAnimal::factory()->create(['id' => $animalId, 'user_number' => $cowNumber]);

    return AnimalSummary::factory()->create([
        'animal_id' => $animalId, 'life_number' => $animal->life_number, 'name' => $name, 'herd_id' => $herdId,
    ]);
}

it('favorites a cow', function () {
    createCow(5, 301);

    $this->post(route('cows.favorite.store', 5))->assertRedirect();

    expect(FavoriteCow::where(['user_id' => $this->user->id, 'animal_id' => 5])->exists())->toBeTrue();
});

it('favorites a cow only once', function () {
    createCow(5, 301);

    $this->post(route('cows.favorite.store', 5));
    $this->post(route('cows.favorite.store', 5));

    expect(FavoriteCow::count())->toBe(1);
});

it('removes a favorite', function () {
    FavoriteCow::factory()->for($this->user)->create(['animal_id' => 5]);

    $this->delete(route('cows.favorite.destroy', 5))->assertRedirect();

    expect(FavoriteCow::count())->toBe(0);
});

it('only removes the favorite of the logged in user', function () {
    FavoriteCow::factory()->create(['animal_id' => 5]);

    $this->delete(route('cows.favorite.destroy', 5));

    expect(FavoriteCow::count())->toBe(1);
});

it('does not favorite a cow that does not exist', function () {
    $this->post(route('cows.favorite.store', 999))->assertNotFound();

    expect(FavoriteCow::count())->toBe(0);
});

it('does not favorite a cow of another herd', function () {
    createCow(5, 301, herdId: 2);
    $this->actingAs(User::factory()->forHerd(1)->create());

    $this->post(route('cows.favorite.store', 5))->assertNotFound();

    expect(FavoriteCow::count())->toBe(0);
});

it('shares the favorites sorted by cow number with a label on every page', function () {
    createCow(7, 450, 'BERTA');
    createCow(5, 301, 'WILLE');
    createCow(6, 120);
    createCow(8, 999);
    foreach ([7, 5, 6] as $animalId) {
        FavoriteCow::factory()->for($this->user)->create(['animal_id' => $animalId]);
    }

    $this->get(route('day-overview'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('favoriteCows', 3)
            ->where('favoriteCows.0.animalId', 6)
            ->where('favoriteCows.1.animalId', 5)
            ->where('favoriteCows.1.cowNumber', 301)
            ->where('favoriteCows.1.name', 'WILLE')
            ->where('favoriteCows.2.animalId', 7)
        );
});

it('keeps the favorites of each user apart', function () {
    createCow(5, 301);
    FavoriteCow::factory()->create(['animal_id' => 5]);

    $this->get(route('day-overview'))
        ->assertInertia(fn (Assert $page) => $page->where('favoriteCows', []));
});

it('leaves out favorites the user can no longer see', function () {
    createCow(5, 301, herdId: 2);
    createCow(6, 302, herdId: 1);
    $user = User::factory()->forHerd(1)->create();
    FavoriteCow::factory()->for($user)->create(['animal_id' => 5]);
    FavoriteCow::factory()->for($user)->create(['animal_id' => 6]);

    $this->actingAs($user)->get(route('day-overview'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('favoriteCows', 1)
            ->where('favoriteCows.0.animalId', 6)
        );
});

it('sends guests to the login page', function () {
    auth()->logout();

    $this->post(route('cows.favorite.store', 5))->assertRedirect(route('login'));
    $this->delete(route('cows.favorite.destroy', 5))->assertRedirect(route('login'));
});
