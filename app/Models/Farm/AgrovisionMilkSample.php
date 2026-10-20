<?php

namespace App\Models\Farm;

use App\Models\Farm\Concerns\CalculatesMeetmelk;
use Carbon\CarbonImmutable;
use Database\Factories\Farm\AgrovisionMilkSampleFactory;
use Illuminate\Database\Eloquent\Attributes\Connection;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A milk production registration (MPR) sample from AgroVision, synced by voerfit-ingest (read-only here).
 *
 * @property int $id
 * @property int $animal_id
 * @property CarbonImmutable $date
 * @property float|null $milk_quantity_mass
 * @property float|null $fat_percentage
 * @property float|null $protein_percentage
 * @property float|null $lactose_percentage
 * @property float|null $cell_value
 * @property float|null $urea
 */
#[Connection('farm')]
#[Table(name: 'agrovision_milk_sample', timestamps: false, dateFormat: 'Y-m-d')]
class AgrovisionMilkSample extends Model
{
    use CalculatesMeetmelk;

    /** @use HasFactory<AgrovisionMilkSampleFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
            'milk_quantity_mass' => 'float',
            'fat_percentage' => 'float',
            'protein_percentage' => 'float',
            'lactose_percentage' => 'float',
            'cell_value' => 'float',
            'urea' => 'float',
        ];
    }

    /**
     * @return BelongsTo<AgrovisionAnimal, $this>
     */
    public function animal(): BelongsTo
    {
        return $this->belongsTo(AgrovisionAnimal::class, 'animal_id', 'animal_id');
    }

    /**
     * Meetmelk: the fat and protein corrected milk (FPCM) in kg.
     */
    public function fatAndProteinCorrectedMilk(): ?float
    {
        return self::meetmelk($this->milk_quantity_mass, $this->fat_percentage, $this->protein_percentage);
    }
}
