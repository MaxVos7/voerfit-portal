<?php

namespace App\Models\Farm\Scopes;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Shared by the scopes that limit a farm query to the herd of the logged in user.
 *
 * A user without a herd, and a query without a user (the console), are not limited.
 */
trait UsersHerd
{
    private function usersHerdId(): ?int
    {
        $user = Auth::user();

        return $user instanceof User ? $user->herd_id : null;
    }
}
