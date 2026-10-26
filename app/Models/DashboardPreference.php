<?php

namespace App\Models;

use Database\Factories\DashboardPreferenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One user's settings for one dashboard, such as the order and visibility of table columns.
 *
 * @property int $id
 * @property int $user_id
 * @property string $dashboard
 * @property array<string, mixed> $settings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'dashboard', 'settings'])]
class DashboardPreference extends Model
{
    /**
     * The dashboard key of the day overview.
     */
    public const string DAY_OVERVIEW = 'day-overview';

    /** @use HasFactory<DashboardPreferenceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
