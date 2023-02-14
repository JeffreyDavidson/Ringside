<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventMatchPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }
}
