<?php

namespace App\Models\Farm;

use App\Models\Farm\Scopes\AnimalsOfUsersHerdScope;
use Carbon\CarbonImmutable;
use Database\Factories\Farm\CameraSnapshotFactory;
use Illuminate\Database\Eloquent\Attributes\Connection;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A camera photo of a cow in the milking robot, stored in Google Cloud Storage (read-only here).
 *
 * @property int $id
 * @property int $animal_id
 * @property CarbonImmutable $time
 * @property string $gcs_path
 * @property string $label Either "before" or "after" the milking.
 */
#[Connection('farm')]
#[ScopedBy(AnimalsOfUsersHerdScope::class)]
#[Table(name: 'core_camerasnapshot', timestamps: false)]
class CameraSnapshot extends Model
{
    /**
     * The bucket the photos are publicly readable from.
     */
    public const string BUCKET_URL = 'https://storage.googleapis.com/farm-camera-snapshots/';

    public const string TIMEZONE = 'Europe/Amsterdam';

    /** @use HasFactory<CameraSnapshotFactory> */
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
            'time' => 'immutable_datetime',
        ];
    }

    /**
     * The moment of the photo on the farm's clock.
     */
    public function localTime(): CarbonImmutable
    {
        return $this->time->setTimezone(self::TIMEZONE);
    }

    public function url(): string
    {
        return self::BUCKET_URL.$this->gcs_path;
    }
}
