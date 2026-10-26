<?php

namespace App\Http\Controllers;

use App\Models\Farm\AnimalSummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteCowsController extends Controller
{
    /**
     * Mark a cow as favorite.
     */
    public function store(Request $request, int $animal): RedirectResponse
    {
        $cow = AnimalSummary::query()->findOrFail($animal);

        $request->user()?->favoriteCows()->firstOrCreate(['animal_id' => $cow->animal_id]);

        return back();
    }

    /**
     * Remove a cow from the favorites.
     */
    public function destroy(Request $request, int $animal): RedirectResponse
    {
        $request->user()?->favoriteCows()->where('animal_id', $animal)->delete();

        return back();
    }
}
