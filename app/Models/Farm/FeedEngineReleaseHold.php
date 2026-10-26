<?php

namespace App\Models\Farm;

use App\Models\Farm\Scopes\AnimalsOfUsersHerdScope;
use Carbon\CarbonImmutable;
use Database\Factories\Farm\FeedEngineReleaseHoldFactory;
use Illuminate\Database\Eloquent\Attributes\Connection;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A cow whose feed is being stepped down to a target after she left the trial (read-only here).
 *
 * @property int $id
 * @property int $animal_id
 * @property int $feed_number
 * @property float|null $hold_kg The level the feed is stepped down to.
 * @property float|null $last_applied_kg
 * @property CarbonImmutable|null $last_applied_date
 * @property bool $active
 * @property int|null $cow_number Selected by the controller from ingest_animal.
 */
#[Connection('farm')]
#[ScopedBy(AnimalsOfUsersHerdScope::class)]
#[Table(name: 'feed_engine_release_hold', timestamps: false, dateFormat: 'Y-m-d')]
class FeedEngineReleaseHold extends Model
{
    /** @use HasFactory<FeedEngineReleaseHoldFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'animal_id' => 'integer',
            'feed_number' => 'integer',
            'hold_kg' => 'float',
            'last_applied_kg' => 'float',
            'last_applied_date' => 'immutable_date',
            'active' => 'boolean',
            'cow_number' => 'integer',
        ];
    }
}
