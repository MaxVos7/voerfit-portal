<?php

namespace App\Models\Farm\Concerns;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Adds the cow number ("koenummer") from ingest_animal to a farm model with a life number.
 *
 * The cow number is reused over the years, so the join runs on life number, which is
 * unique in ingest_animal and can never fan out. Life numbers are written with and
 * without spaces across the source systems, so spaces and case are ignored.
 */
trait HasCowNumber
{
    /**
     * The qualified life number column to match on, such as "core_animal_summary.life_number".
     *
     * @return literal-string
     */
    abstract protected function lifeNumberColumn(): string;

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function withCowNumber(Builder $query): void
    {
        $lifeNumber = $this->lifeNumberColumn();

        $query->leftJoin('ingest_animal', fn ($join) => $join->on(
            DB::raw("upper(replace(ingest_animal.life_number, ' ', ''))"),
            '=',
            DB::raw("upper(replace({$lifeNumber}, ' ', ''))"),
        ))->addSelect('ingest_animal.user_number as cow_number');
    }
}
