<?php

namespace App\Models\Farm;

use Database\Factories\Farm\AgrovisionAnimalFactory;
use Illuminate\Database\Eloquent\Attributes\Connection;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An animal as known in the AgroVision CowVision portal, synced by voerfit-ingest (read-only here).
 *
 * @property int $animal_id AgroVision's own id, not the platform's animal_id.
 * @property string|null $registration_id The life number.
 */
#[Connection('farm')]
#[Table(name: 'agrovision_animal', key: 'animal_id', incrementing: false, timestamps: false)]
class AgrovisionAnimal extends Model
{
    /** @use HasFactory<AgrovisionAnimalFactory> */
    use HasFactory;

    /**
     * @return HasMany<AgrovisionMilkSample, $this>
     */
    public function milkSamples(): HasMany
    {
        return $this->hasMany(AgrovisionMilkSample::class, 'animal_id', 'animal_id');
    }
}
