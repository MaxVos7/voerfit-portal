<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FormatsDutchDates;
use App\Http\Resources\DayOverviewCowResource;
use App\Models\Farm\CowDailySummary;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DayOverviewController extends Controller
{
    use FormatsDutchDates;

    /**
     * Show the lactating cows of one day, the portal's copy of Grafana's "Dagoverzicht koeien".
     */
    public function __invoke(Request $request): Response
    {
        $days = CowDailySummary::availableDays();
        $selectedDay = $this->parseDay($request->query('dag')) ?? $days->first();

        return Inertia::render('farm/DayOverview', [
            'days' => $days->map(fn (CarbonImmutable $day): array => [
                'value' => $day->toDateString(),
                'label' => $this->dutchLabel($day),
            ])->all(),
            'selectedDay' => $selectedDay?->toDateString(),
            'selectedDayLabel' => $selectedDay ? $this->dutchLabel($selectedDay) : null,
            'columns' => $request->user()?->dayOverviewColumns() ?? [],
            'cows' => $selectedDay
                ? DayOverviewCowResource::collection($this->cows($selectedDay))->resolve()
                : [],
        ]);
    }

    /**
     * @return Collection<int, CowDailySummary>
     */
    private function cows(CarbonImmutable $day): Collection
    {
        return CowDailySummary::query()
            ->select('core_cow_daily_summary.*')
            ->lactatingOn($day)
            ->withCowNumber()
            ->withLatestBcs($day)
            ->get()
            ->sortBy(fn (CowDailySummary $cow): int => $cow->cow_number ?? PHP_INT_MAX)
            ->values();
    }

    private function parseDay(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        $day = CarbonImmutable::createFromFormat('!Y-m-d', $value);

        // createFromFormat rolls impossible dates such as 2026-13-45 over into valid ones.
        return $day !== null && $day->toDateString() === $value ? $day : null;
    }
}
