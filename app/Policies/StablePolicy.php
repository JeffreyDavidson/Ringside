<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Stable;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StablePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create stables.
     */
    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can update a stable.
     */
    public function update(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can delete a stable.
     */
    public function delete(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can restore a deleted stable.
     */
    public function restore(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can activate a stable.
     */
    public function activate(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can deactivate a stable.
     */
    public function deactivate(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can retire a stable.
     */
    public function retire(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can unretire a stable.
     */
    public function unretire(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a list of stables.
     */
    public function viewList(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a profile for a stable.
     */
    public function view(User $user, Stable $stable): bool
    {
        if ($stable->user !== null && $stable->user->is($user)) {
            return true;
        }

        return $user->isAdministrator();
    }
}
