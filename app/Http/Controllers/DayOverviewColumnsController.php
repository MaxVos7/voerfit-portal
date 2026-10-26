<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDayOverviewColumnsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DayOverviewColumnsController extends Controller
{
    /**
     * Save the order and visibility of the user's day overview columns.
     */
    public function update(UpdateDayOverviewColumnsRequest $request): RedirectResponse
    {
        $columns = [];

        foreach ((array) $request->validated('columns') as $column) {
            $columns[] = [
                'key' => (string) $column['key'],
                'visible' => filter_var($column['visible'], FILTER_VALIDATE_BOOLEAN),
            ];
        }

        $request->user()?->saveDayOverviewColumns($columns);

        return back();
    }

    /**
     * Restore the default columns.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->user()?->resetDayOverviewColumns();

        return back();
    }
}
