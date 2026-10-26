<?php

namespace App\Http\Controllers;

use App\Models\Farm\ControlGroupAssignment;
use App\Models\Farm\FeedEngineReleaseHold;
use App\Models\Farm\RuleValue;
use Inertia\Inertia;
use Inertia\Response;

class FeedRulesController extends Controller
{
    /**
     * Explain how the system steers the concentrate feed, the portal's copy of Grafana's "Voerregels".
     */
    public function __invoke(): Response
    {
        return Inertia::render('farm/FeedRules', [
            'rules' => RuleValue::query()->pluck('value', 'name')->all(),
            'holds' => $this->holds(),
            'distribution' => $this->distribution(),
        ]);
    }

    /**
     * The cows whose feed is being stepped down after they left the trial.
     *
     * @return list<array{cowNumber: ?int, feedNumber: int, currentKg: ?float, targetKg: ?float, lastStepDate: ?string}>
     */
    private function holds(): array
    {
        return array_values(FeedEngineReleaseHold::query()
            ->select('feed_engine_release_hold.*')
            ->leftJoin('ingest_animal', 'ingest_animal.id', '=', 'feed_engine_release_hold.animal_id')
            ->addSelect('ingest_animal.user_number as cow_number')
            ->where('feed_engine_release_hold.active', true)
            ->orderBy('ingest_animal.user_number')
            ->orderBy('feed_engine_release_hold.feed_number')
            ->get()
            ->map(fn (FeedEngineReleaseHold $hold): array => [
                'cowNumber' => $hold->cow_number,
                'feedNumber' => $hold->feed_number,
                'currentKg' => $hold->last_applied_kg,
                'targetKg' => $hold->hold_kg,
                'lastStepDate' => $hold->last_applied_date?->toDateString(),
            ])
            ->all());
    }

    /**
     * How many trial cows currently get each feed deviation.
     *
     * @return list<array{delta: ?float, cows: int}>
     */
    private function distribution(): array
    {
        return array_values(ControlGroupAssignment::query()
            ->toBase()
            ->where('active', true)
            ->where('belongs_to_test_group', true)
            ->where('experiment_id', ControlGroupAssignment::FEED_ADJUSTMENT)
            ->groupBy('delta_feed')
            ->orderBy('delta_feed')
            ->selectRaw('delta_feed, count(*) as cows')
            ->get()
            ->map(fn (object $row): array => [
                'delta' => $row->delta_feed === null ? null : (float) $row->delta_feed,
                'cows' => (int) $row->cows,
            ])
            ->all());
    }
}
