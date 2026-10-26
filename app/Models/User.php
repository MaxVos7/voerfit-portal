<?php

namespace App\Models;

use App\Enums\DayOverviewColumn;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property int|null $herd_id The herd the user is limited to; null means every herd.
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'herd_id' => 'integer',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<DashboardPreference, $this>
     */
    public function dashboardPreferences(): HasMany
    {
        return $this->hasMany(DashboardPreference::class);
    }

    /**
     * The user's day overview columns in their chosen order.
     *
     * Columns the user has not placed yet, such as new ones, follow at the end as visible,
     * and saved columns that no longer exist are dropped.
     *
     * @return list<array{key: string, visible: bool}>
     */
    public function dayOverviewColumns(): array
    {
        $saved = $this->dashboardPreferences()
            ->where('dashboard', DashboardPreference::DAY_OVERVIEW)
            ->first()?->settings['columns'] ?? [];

        $columns = [];

        foreach ($saved as $column) {
            $key = DayOverviewColumn::tryFrom($column['key'] ?? '');

            if ($key !== null && ! isset($columns[$key->value])) {
                $columns[$key->value] = ['key' => $key->value, 'visible' => (bool) ($column['visible'] ?? true)];
            }
        }

        foreach (DayOverviewColumn::cases() as $column) {
            $columns[$column->value] ??= ['key' => $column->value, 'visible' => true];
        }

        return array_values($columns);
    }

    /**
     * @param  list<array{key: string, visible: bool}>  $columns
     */
    public function saveDayOverviewColumns(array $columns): void
    {
        $this->dashboardPreferences()->updateOrCreate(
            ['dashboard' => DashboardPreference::DAY_OVERVIEW],
            ['settings' => ['columns' => $columns]],
        );
    }

    public function resetDayOverviewColumns(): void
    {
        $this->dashboardPreferences()->where('dashboard', DashboardPreference::DAY_OVERVIEW)->delete();
    }
}
