<?php

namespace App\Http\Middleware;

use App\Models\Farm\AnimalSummary;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'favoriteCows' => fn (): array => $this->favoriteCows($request->user()),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * The cows the user marked as favorite, by cow number, for the sidebar and the stars.
     *
     * Favorites the user can no longer see, such as cows of another herd, are left out.
     *
     * @return list<array{animalId: int, cowNumber: int|null, name: string|null, label: string}>
     */
    private function favoriteCows(?User $user): array
    {
        $animalIds = $user?->favoriteCows()->pluck('animal_id');

        if ($animalIds === null || $animalIds->isEmpty()) {
            return [];
        }

        return array_values(AnimalSummary::query()
            ->select('core_animal_summary.*')
            ->withCowNumber()
            ->whereIn('core_animal_summary.animal_id', $animalIds)
            ->get()
            ->sortBy([
                fn (AnimalSummary $a, AnimalSummary $b): int => ($a->cow_number ?? PHP_INT_MAX) <=> ($b->cow_number ?? PHP_INT_MAX),
                fn (AnimalSummary $a, AnimalSummary $b): int => (string) $a->life_number <=> (string) $b->life_number,
            ])
            ->map(fn (AnimalSummary $animal): array => [
                'animalId' => $animal->animal_id,
                'cowNumber' => $animal->cow_number,
                'name' => $animal->name,
                'label' => $animal->label(),
            ])
            ->all());
    }
}
