<?php

namespace App\Models\Farm;

use App\Models\Farm\Scopes\AnimalsOfUsersHerdScope;
use Database\Factories\Farm\ControlGroupAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Connection;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A cow's assignment in an experiment, with the feed deviation she currently gets (read-only here).
 *
 * @property int $id
 * @property int $animal_id
 * @property string $experiment_id
 * @property bool $active
 * @property bool $belongs_to_test_group
 * @property float|null $delta_feed Deviation from the feeding schedule in kg.
 */
#[Connection('farm')]
#[ScopedBy(AnimalsOfUsersHerdScope::class)]
#[Table(name: 'experiment_controlgroupassignment', timestamps: false)]
class ControlGroupAssignment extends Model
{
    /**
     * The experiment that steers the concentrate feed.
     */
    public const string FEED_ADJUSTMENT = 'feed_adjustment';

    /** @use HasFactory<ControlGroupAssignmentFactory> */
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
            'active' => 'boolean',
            'belongs_to_test_group' => 'boolean',
            'delta_feed' => 'float',
        ];
    }
}
