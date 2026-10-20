<?php

namespace App\Models\Farm;

use App\Models\Farm\Concerns\CalculatesMeetmelk;
use App\Models\Farm\Concerns\HasCowNumber;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\Farm\CowDailySummaryFactory;
use Illuminate\Database\Eloquent\Attributes\Connection;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * One row per animal per day, owned and refreshed by voerfit-ingest (read-only here).
 *
 * The table is a TimescaleDB hypertable with the composite key (time, animal_id),
 * which Eloquent cannot model, so rows are only read and never updated.
 *
 * @property CarbonImmutable $time
 * @property int $animal_id
 * @property string|null $life_number
 * @property int|null $days_in_milk
 * @property int|null $lactation_number
 * @property bool|null $is_lactating
 * @property float|null $interval_milk_production
 * @property float|null $bcs
 * @property float|null $bcs_sem Standard error of the day's BCS estimate.
 * @property float|null $feed_total_sum_n1
 * @property float|null $feed_total_sum_n3
 * @property float|null $feed_total_original_sum_n1 Feed amount before any AI adjustment.
 * @property float|null $feed_total_original_sum_n3 Feed amount before any AI adjustment.
 * @property int|null $feed_rest_last_n1 Feed left over in grams.
 * @property int|null $feed_rest_last_n3 Feed left over in grams.
 * @property int|null $mdp_milkings
 * @property int|null $mdp_refusals
 * @property float|null $lab_milk_quantity_mass
 * @property float|null $lab_fat_percentage
 * @property float|null $lab_protein_percentage
 * @property float|null $lab_lactose_percentage
 * @property float|null $lab_ureum
 * @property int|null $lab_cell_number
 * @property CarbonImmutable|null $lab_sample_date
 * @property int|null $cow_number Selected by withCowNumber().
 * @property float|null $latest_bcs Selected by withLatestBcs().
 * @property string|null $latest_bcs_time Selected by withLatestBcs().
 */
#[Connection('farm')]
#[Table(name: 'core_cow_daily_summary', incrementing: false, timestamps: false, dateFormat: 'Y-m-d')]
class CowDailySummary extends Model
{
    use CalculatesMeetmelk;
    use HasCowNumber;

    /** @use HasFactory<CowDailySummaryFactory> */
    use HasFactory;

    /**
     * A BCS reading older than this many days is no longer considered current.
     */
    public const int BCS_OUTDATED_AFTER_DAYS = 15;

    /**
     * @return literal-string
     */
    protected function lifeNumberColumn(): string
    {
        return 'core_cow_daily_summary.life_number';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'time' => 'immutable_date',
            'animal_id' => 'integer',
            'days_in_milk' => 'integer',
            'lactation_number' => 'integer',
            'is_lactating' => 'boolean',
            'interval_milk_production' => 'float',
            'bcs' => 'float',
            'bcs_sem' => 'float',
            'feed_total_sum_n1' => 'float',
            'feed_total_sum_n3' => 'float',
            'feed_total_original_sum_n1' => 'float',
            'feed_total_original_sum_n3' => 'float',
            'feed_rest_last_n1' => 'integer',
            'feed_rest_last_n3' => 'integer',
            'mdp_milkings' => 'integer',
            'mdp_refusals' => 'integer',
            'lab_milk_quantity_mass' => 'float',
            'lab_fat_percentage' => 'float',
            'lab_protein_percentage' => 'float',
            'lab_lactose_percentage' => 'float',
            'lab_ureum' => 'float',
            'lab_cell_number' => 'integer',
            'lab_sample_date' => 'immutable_date',
            'cow_number' => 'integer',
            'latest_bcs' => 'float',
        ];
    }

    /**
     * The days that have data within the last 400 days before today, newest first.
     *
     * @return Collection<int, CarbonImmutable>
     */
    public static function availableDays(): Collection
    {
        $today = CarbonImmutable::today();

        return static::query()
            ->where('time', '>', $today->subDays(400)->toDateString())
            ->where('time', '<', $today->toDateString())
            ->distinct()
            ->orderByDesc('time')
            ->pluck('time')
            ->map(fn (mixed $time): CarbonImmutable => CarbonImmutable::parse($time)->startOfDay())
            ->values();
    }

    /**
     * Limit the query to the cows that were lactating on the given day.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function lactatingOn(Builder $query, CarbonInterface $day): void
    {
        $query->where('core_cow_daily_summary.time', $day->toDateString())
            ->where('core_cow_daily_summary.is_lactating', true);
    }

    /**
     * Add each cow's most recent BCS reading of the year up to and including the given day.
     *
     * BCS is only measured occasionally, so most days carry no reading of their own.
     * The fixed date bounds let TimescaleDB skip chunks outside that year, which
     * makes the lookup several times faster than bounding on the outer row's time.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withLatestBcs(Builder $query, CarbonInterface $day): void
    {
        $latest = fn (string $column) => DB::connection($this->getConnectionName())
            ->table('core_cow_daily_summary as bcs_reading')
            ->select("bcs_reading.{$column}")
            ->whereColumn('bcs_reading.animal_id', 'core_cow_daily_summary.animal_id')
            ->where('bcs_reading.time', '<=', $day->toDateString())
            ->where('bcs_reading.time', '>', $day->toImmutable()->subYear()->toDateString())
            ->whereNotNull('bcs_reading.bcs')
            ->orderByDesc('bcs_reading.time')
            ->limit(1);

        $query->addSelect([
            'latest_bcs' => $latest('bcs'),
            'latest_bcs_time' => $latest('time'),
        ]);
    }

    /**
     * The AI adjustment of a feed: the applied amount minus the original amount.
     *
     * Returns null when nothing was adjusted or the original amount is unknown.
     */
    public function feedAdjustment(int $feedNumber): ?float
    {
        $applied = $this->getAttribute("feed_total_sum_n{$feedNumber}");
        $original = $this->getAttribute("feed_total_original_sum_n{$feedNumber}");

        if ($applied === null || $original === null) {
            return null;
        }

        $difference = round($applied - $original, 2);

        return $difference == 0 ? null : $difference;
    }

    /**
     * The age in days of the latest BCS reading on the row's day.
     */
    public function bcsAgeDays(): ?int
    {
        if ($this->latest_bcs_time === null) {
            return null;
        }

        return (int) CarbonImmutable::parse($this->latest_bcs_time)->startOfDay()->diffInDays($this->time);
    }

    public function bcsIsOutdated(): bool
    {
        return ($this->bcsAgeDays() ?? 0) > self::BCS_OUTDATED_AFTER_DAYS;
    }

    /**
     * Meetmelk: the milk sample's fat and protein corrected milk (FPCM) in kg.
     */
    public function fatAndProteinCorrectedMilk(): ?float
    {
        return self::meetmelk($this->lab_milk_quantity_mass, $this->lab_fat_percentage, $this->lab_protein_percentage);
    }
}
