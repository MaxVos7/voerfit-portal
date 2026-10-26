<?php

namespace App\Models\Farm\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Limits a table that only knows the animal to the animals of the herd of the logged in user.
 *
 * @implements Scope<Model>
 */
class AnimalsOfUsersHerdScope implements Scope
{
    use UsersHerd;

    /**
     * @param  Builder<covariant Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $herdId = $this->usersHerdId();

        if ($herdId === null) {
            return;
        }

        $builder->whereIn(
            "{$model->getTable()}.animal_id",
            $model->getConnection()
                ->table('core_animal_summary')
                ->select('animal_id')
                ->where('herd_id', $herdId),
        );
    }
}
