<?php

namespace App\Http\Controllers;

use App\Http\Resources\DayOverviewCowResource;
use App\Models\Farm\CowDailySummary;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DayOverviewController extends Controller
{
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
            'cows' => $selectedDay
                ? DayOverviewCowResource::collection($this->cows($selectedDay))->resolve()
                : [],
            'averages' => $selectedDay ? $this->averages($selectedDay) : null,
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

    /**
     * @return array{milkProduction: ?float, fat: ?float, protein: ?float, urea: ?float, bcs: ?float}
     */
    private function averages(CarbonImmutable $day): array
    {
        $averages = CowDailySummary::query()
            ->lactatingOn($day)
            ->toBase()
            ->selectRaw('avg(interval_milk_production) as milk_production')
            ->selectRaw('avg(lab_fat_percentage) as fat')
            ->selectRaw('avg(lab_protein_percentage) as protein')
            ->selectRaw('avg(lab_ureum) as urea')
            ->selectRaw('avg(bcs) as bcs')
            ->first();

        $round = fn (mixed $value): ?float => $value === null ? null : round((float) $value, 2);

        return [
            'milkProduction' => $round($averages?->milk_production),
            'fat' => $round($averages?->fat),
            'protein' => $round($averages?->protein),
            'urea' => $round($averages?->urea),
            'bcs' => $round($averages?->bcs),
        ];
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

    /**
     * "19 oktober", with the year only when it is not the current year.
     */
    private function dutchLabel(CarbonImmutable $day): string
    {
        $format = $day->isCurrentYear() ? 'j F' : 'j F Y';

        return $day->settings(['locale' => 'nl'])->translatedFormat($format);
    }
}
