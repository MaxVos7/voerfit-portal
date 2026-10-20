<?php

namespace App\Models\Farm;

use App\Models\Farm\Concerns\HasCowNumber;
use Database\Factories\Farm\AnimalSummaryFactory;
use Illuminate\Database\Eloquent\Attributes\Connection;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One current-snapshot row per animal, owned and refreshed by voerfit-ingest (read-only here).
 *
 * @property int $animal_id
 * @property string|null $life_number
 * @property string|null $name
 * @property int|null $cow_number Selected by withCowNumber().
 */
#[Connection('farm')]
#[Table(name: 'core_animal_summary', key: 'animal_id', incrementing: false, timestamps: false)]
class AnimalSummary extends Model
{
    use HasCowNumber;

    /** @use HasFactory<AnimalSummaryFactory> */
    use HasFactory;

    /**
     * @return literal-string
     */
    protected function lifeNumberColumn(): string
    {
        return 'core_animal_summary.life_number';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'animal_id' => 'integer',
            'cow_number' => 'integer',
        ];
    }

    /**
     * "301 - NL 656185061 - WILLE OLLA", leaving out whatever is unknown.
     */
    public function label(): string
    {
        return collect([$this->cow_number, $this->life_number, $this->name])
            ->filter(fn (mixed $part): bool => $part !== null && $part !== '')
            ->implode(' - ');
    }
}
