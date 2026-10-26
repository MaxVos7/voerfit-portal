<?php

namespace App\Models\Farm\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Limits a table that has a herd_id of its own to the herd of the logged in user.
 *
 * @implements Scope<Model>
 */
class OwnHerdScope implements Scope
{
    use UsersHerd;

    /**
     * @param  Builder<covariant Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $herdId = $this->usersHerdId();

        if ($herdId !== null) {
            $builder->where("{$model->getTable()}.herd_id", $herdId);
        }
    }
}
