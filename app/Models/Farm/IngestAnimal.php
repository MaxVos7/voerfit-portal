<?php

namespace App\Models\Farm;

use Database\Factories\Farm\IngestAnimalFactory;
use Illuminate\Database\Eloquent\Attributes\Connection;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Master animal record synced from Lely by voerfit-ingest (read-only here).
 *
 * @property int $id
 * @property string $life_number
 * @property int|null $user_number The "koenummer" the farmer knows the cow by; reused over the years.
 */
#[Connection('farm')]
#[Table(name: 'ingest_animal', timestamps: false)]
class IngestAnimal extends Model
{
    /** @use HasFactory<IngestAnimalFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_number' => 'integer',
        ];
    }
}
