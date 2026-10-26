<?php

namespace App\Models;

use Database\Factories\FavoriteCowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A cow a user marked as favorite; the animal lives in the farm database, so there is no relation to it.
 *
 * @property int $id
 * @property int $user_id
 * @property int $animal_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'animal_id'])]
class FavoriteCow extends Model
{
    /** @use HasFactory<FavoriteCowFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
