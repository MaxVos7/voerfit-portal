<?php

namespace App\Http\Controllers;

use App\Models\Farm\AgrovisionMilkSample;
use App\Models\Farm\AnimalSummary;
use App\Models\Farm\CowDailySummary;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CowOverviewController extends Controller
{
    /**
     * The lactation option that shows the last 400 days instead of one lactation.
     */
    private const string LAST_400_DAYS = 'laatste-400-dagen';

    /**
     * Show one cow over a lactation, the portal's copy of Grafana's "Voer / melkproductie per koe".
     */
    public function __invoke(Request $request, int $animal): Response
    {
        $cow = AnimalSummary::query()
            ->select('core_animal_summary.*')
            ->withCowNumber()
            ->findOrFail($animal);

        $lactations = $this->lactations($cow);
        $selectedLactation = $this->selectedLactation($request->query('lactatie'), $lactations);
        [$from, $to] = $this->period($lactations->firstWhere('lactation_number', $selectedLactation));
        $days = $this->days($cow, $from, $to);

        return Inertia::render('farm/CowOverview', [
            'cow' => [
                'animalId' => $cow->animal_id,
                'cowNumber' => $cow->cow_number,
                'lifeNumber' => $cow->life_number,
                'name' => $cow->name,
                'label' => $cow->label(),
            ],
            'cows' => $this->cowOptions(),
            'lactations' => [
                ...$lactations->map(fn (object $lactation): array => [
                    'value' => (string) $lactation->lactation_number,
                    'label' => sprintf(
                        'Lactatie %d (%s t/m %s)',
                        $lactation->lactation_number,
                        CarbonImmutable::parse($lactation->first_day)->format('d-m-Y'),
                        CarbonImmutable::parse($lactation->last_day)->format('d-m-Y'),
                    ),
                ]),
                ['value' => self::LAST_400_DAYS, 'label' => 'Laatste 400 dagen'],
            ],
            'selectedLactation' => $selectedLactation === null ? self::LAST_400_DAYS : (string) $selectedLactation,
            'summary' => $this->summary($days, $selectedLactation),
            'chart' => [
                'days' => $days->map(fn (CowDailySummary $day): array => [
                    'date' => $day->time->toDateString(),
                    'daysInMilk' => $day->days_in_milk,
                    'milkProduction' => $day->interval_milk_production,
                    'feedN1' => $day->feed_total_sum_n1 ?? 0,
                    'feedN1Original' => $day->feed_total_original_sum_n1 ?? $day->feed_total_sum_n1 ?? 0,
                    'feedN3' => $day->feed_total_sum_n3 ?? 0,
                    'feedN3Original' => $day->feed_total_original_sum_n3 ?? $day->feed_total_sum_n3 ?? 0,
                    'restFeedN1' => ($day->feed_rest_last_n1 ?? 0) / 1000,
                    'restFeedN3' => ($day->feed_rest_last_n3 ?? 0) / 1000,
                    'milkings' => $day->mdp_milkings,
                    'refusals' => $day->mdp_refusals,
                ])->all(),
                'bcs' => $this->fortnightlyBcs($days),
                'milkSamples' => $this->milkSamples($cow, $from, $to),
            ],
        ]);
    }

    /**
     * @return list<array{animalId: int, label: string}>
     */
    private function cowOptions(): array
    {
        return array_values(AnimalSummary::query()
            ->select('core_animal_summary.*')
            ->withCowNumber()
            ->get()
            ->sortBy([
                fn (AnimalSummary $a, AnimalSummary $b): int => ($a->cow_number ?? PHP_INT_MAX) <=> ($b->cow_number ?? PHP_INT_MAX),
                fn (AnimalSummary $a, AnimalSummary $b): int => (string) $a->life_number <=> (string) $b->life_number,
            ])
            ->map(fn (AnimalSummary $animal): array => [
                'animalId' => $animal->animal_id,
                'label' => $animal->label(),
            ])
            ->all());
    }

    /**
     * The cow's lactations up to yesterday, newest first.
     *
     * @return Collection<int, object{lactation_number: int, first_day: string, last_day: string}>
     */
    private function lactations(AnimalSummary $cow): Collection
    {
        /** @var Collection<int, object{lactation_number: int, first_day: string, last_day: string}> */
        return CowDailySummary::query()
            ->toBase()
            ->where('animal_id', $cow->animal_id)
            ->whereNotNull('lactation_number')
            ->where('time', '<', CarbonImmutable::today()->toDateString())
            ->groupBy('lactation_number')
            ->orderByDesc('lactation_number')
            ->selectRaw('lactation_number, min(time) as first_day, max(time) as last_day')
            ->get()
            ->map(function (object $lactation): object {
                $lactation->lactation_number = (int) $lactation->lactation_number;

                return $lactation;
            });
    }

    /**
     * The requested lactation, else the latest one; null means the last 400 days.
     *
     * @param  Collection<int, object{lactation_number: int, first_day: string, last_day: string}>  $lactations
     */
    private function selectedLactation(mixed $requested, Collection $lactations): ?int
    {
        if ($requested === self::LAST_400_DAYS) {
            return null;
        }

        $numbers = $lactations->pluck('lactation_number');

        if (is_string($requested) && ctype_digit($requested) && $numbers->contains((int) $requested)) {
            return (int) $requested;
        }

        return $numbers->first();
    }

    /**
     * The first and last day to show: the whole lactation, or the last 400 days, never past yesterday.
     *
     * The period is bounded by date rather than by lactation number, so a day with a
     * missing lactation number does not punch a gap in the middle of the chart. The
     * lactations only run up to yesterday already.
     *
     * @param  object{lactation_number: int, first_day: string, last_day: string}|null  $lactation
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    private function period(?object $lactation): array
    {
        $today = CarbonImmutable::today();

        if ($lactation === null) {
            return [$today->subDays(400), $today->subDay()];
        }

        return [
            CarbonImmutable::parse($lactation->first_day)->startOfDay(),
            CarbonImmutable::parse($lactation->last_day)->startOfDay(),
        ];
    }

    /**
     * @return Collection<int, CowDailySummary>
     */
    private function days(AnimalSummary $cow, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return CowDailySummary::query()
            ->select([
                'time', 'animal_id', 'days_in_milk', 'lactation_number', 'interval_milk_production',
                'bcs', 'bcs_sem', 'feed_total_sum_n1', 'feed_total_sum_n3', 'feed_total_original_sum_n1',
                'feed_total_original_sum_n3', 'feed_rest_last_n1', 'feed_rest_last_n3', 'mdp_milkings',
                'mdp_refusals', 'lab_fat_percentage', 'lab_protein_percentage', 'lab_ureum',
            ])
            ->where('animal_id', $cow->animal_id)
            ->whereBetween('time', [$from->toDateString(), $to->toDateString()])
            ->orderBy('time')
            ->get();
    }

    /**
     * The key figures of the latest day in the period that has a milk production.
     *
     * @param  Collection<int, CowDailySummary>  $days
     * @return array<string, mixed>|null
     */
    private function summary(Collection $days, ?int $lactation): ?array
    {
        $day = $days
            ->filter(fn (CowDailySummary $day): bool => $day->interval_milk_production !== null)
            ->filter(fn (CowDailySummary $day): bool => $lactation === null || $day->lactation_number === $lactation)
            ->last();

        if ($day === null) {
            return null;
        }

        return [
            'date' => $day->time->toDateString(),
            'dateLabel' => $day->time->settings(['locale' => 'nl'])
                ->translatedFormat($day->time->isCurrentYear() ? 'j F' : 'j F Y'),
            'daysInMilk' => $day->days_in_milk ?? $this->derivedDaysInMilk($day),
            'lactationNumber' => $day->lactation_number,
            'milkProduction' => $day->interval_milk_production,
            'fat' => $day->lab_fat_percentage,
            'protein' => $day->lab_protein_percentage,
            'urea' => $day->lab_ureum,
            'feedN1' => $day->feed_total_sum_n1,
            'feedN1Delta' => $day->feedAdjustment(1),
            'feedN3' => $day->feed_total_sum_n3,
            'feedN3Delta' => $day->feedAdjustment(3),
        ];
    }

    /**
     * Days since the start of the lactation, for the dry-off day that has no days in milk recorded.
     */
    private function derivedDaysInMilk(CowDailySummary $day): ?int
    {
        if ($day->lactation_number === null) {
            return null;
        }

        $firstDay = CowDailySummary::query()
            ->where('animal_id', $day->animal_id)
            ->where('lactation_number', $day->lactation_number)
            ->min('time');

        return $firstDay === null ? null : (int) CarbonImmutable::parse($firstDay)->diffInDays($day->time);
    }

    /**
     * One BCS point per fortnight: the mean of its readings, plotted on its last measured day.
     *
     * Fortnights are counted the way the Grafana panel does: Monday-based weeks,
     * grouped by floor(epoch of the week start / 14 days).
     *
     * @param  Collection<int, CowDailySummary>  $days
     * @return list<array{date: string, bcs: float, sem: float|null}>
     */
    private function fortnightlyBcs(Collection $days): array
    {
        return array_values($days
            ->filter(fn (CowDailySummary $day): bool => $day->bcs !== null)
            ->groupBy(fn (CowDailySummary $day): int => intdiv($day->time->startOfWeek()->getTimestamp(), 14 * 24 * 60 * 60))
            ->map(function (Collection $fortnight): array {
                $sems = $fortnight->pluck('bcs_sem')->filter(fn (?float $sem): bool => $sem !== null);

                return [
                    'date' => $fortnight->last()->time->toDateString(),
                    'bcs' => round($fortnight->avg('bcs'), 2),
                    'sem' => $sems->isEmpty() ? null : round($sems->avg(), 2),
                ];
            })
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function milkSamples(AnimalSummary $cow, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return array_values(AgrovisionMilkSample::query()
            ->whereHas('animal', fn (Builder $query) => $query->where('registration_id', $cow->life_number))
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date')
            ->get()
            ->map(fn (AgrovisionMilkSample $sample): array => [
                'date' => $sample->date->toDateString(),
                'milk' => $sample->milk_quantity_mass,
                'fpcm' => $sample->fatAndProteinCorrectedMilk(),
                'fat' => $sample->fat_percentage,
                'protein' => $sample->protein_percentage,
                'lactose' => $sample->lactose_percentage,
                'urea' => $sample->urea,
                'cellCount' => $sample->cell_value === null ? null : (int) round($sample->cell_value),
            ])
            ->all());
    }
}
