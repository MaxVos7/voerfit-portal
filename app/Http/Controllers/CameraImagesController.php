<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FormatsDutchDates;
use App\Models\Farm\AnimalSummary;
use App\Models\Farm\CameraSnapshot;
use App\Models\Farm\CowDailySummary;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CameraImagesController extends Controller
{
    use FormatsDutchDates;

    /**
     * Show the photos one cow had taken before milking on one day, the portal's copy of Grafana's "Camerabeelden".
     */
    public function __invoke(Request $request, int $animal): Response
    {
        $cow = AnimalSummary::query()
            ->select('core_animal_summary.*')
            ->withCowNumber()
            ->findOrFail($animal);

        $snapshots = CameraSnapshot::query()
            ->where('animal_id', $cow->animal_id)
            ->where('label', 'before')
            ->orderBy('time')
            ->get();

        $days = $snapshots
            ->groupBy(fn (CameraSnapshot $snapshot): string => $snapshot->localTime()->toDateString())
            ->keys()
            ->sortDesc()
            ->values();

        $selectedDay = $this->selectedDay($request->query('dag'), $days);

        return Inertia::render('farm/CameraImages', [
            'cow' => [
                'animalId' => $cow->animal_id,
                'cowNumber' => $cow->cow_number,
                'lifeNumber' => $cow->life_number,
                'name' => $cow->name,
                'label' => $cow->label(),
            ],
            'cows' => $this->cowOptions(),
            'days' => $days->map(fn (string $day): array => [
                'value' => $day,
                'label' => $this->dutchLabel(CarbonImmutable::parse($day)),
            ])->all(),
            'selectedDay' => $selectedDay,
            'photos' => $snapshots
                ->filter(fn (CameraSnapshot $snapshot): bool => $snapshot->localTime()->toDateString() === $selectedDay)
                ->map(fn (CameraSnapshot $snapshot): array => [
                    'url' => $snapshot->url(),
                    'time' => $snapshot->localTime()->format('H:i'),
                ])
                ->values()
                ->all(),
            'summary' => $selectedDay === null ? null : $this->summary($cow, $selectedDay),
        ]);
    }

    /**
     * The requested day when it has photos, else the latest day that has.
     *
     * @param  Collection<int, string>  $days
     */
    private function selectedDay(mixed $requested, Collection $days): ?string
    {
        return is_string($requested) && $days->contains($requested) ? $requested : $days->first();
    }

    /**
     * The cows that have photos.
     *
     * @return list<array{animalId: int, label: string}>
     */
    private function cowOptions(): array
    {
        return array_values(AnimalSummary::query()
            ->select('core_animal_summary.*')
            ->withCowNumber()
            ->whereIn('core_animal_summary.animal_id', CameraSnapshot::query()->select('animal_id'))
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
     * The cow's key figures on the day of the photos.
     *
     * @return array<string, mixed>|null
     */
    private function summary(AnimalSummary $cow, string $day): ?array
    {
        $summary = CowDailySummary::query()
            ->where('animal_id', $cow->animal_id)
            ->where('time', $day)
            ->first();

        if ($summary === null) {
            return null;
        }

        return [
            'daysInMilk' => $summary->days_in_milk,
            'lactationNumber' => $summary->lactation_number,
            'milkProduction' => $summary->interval_milk_production,
            'fat' => $summary->lab_fat_percentage,
            'protein' => $summary->lab_protein_percentage,
            'urea' => $summary->lab_ureum,
            'feedN1' => $summary->feed_total_sum_n1,
            'feedN1Delta' => $summary->feedAdjustment(1),
            'feedN3' => $summary->feed_total_sum_n3,
            'feedN3Delta' => $summary->feedAdjustment(3),
        ];
    }
}
