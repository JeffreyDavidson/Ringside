<?php

namespace App\Policies;

use App\Models\Stable;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StablePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create stables.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can update a stable.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function update(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can delete a stable.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function delete(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can restore a deleted stable.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function restore(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can activate a stable.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Stable  $stable
     * @return bool
     */
    public function activate(User $user, Stable $stable)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can deactivate a stable.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Stable  $stable
     * @return bool
     */
    public function deactivate(User $user, Stable $stable)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can retire a stable.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Stable  $stable
     * @return bool
     */
    public function retire(User $user, Stable $stable)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can unretire a stable.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Stable  $stable
     * @return bool
     */
    public function unretire(User $user, Stable $stable)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can disassemble a stable.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Stable  $stable
     * @return bool
     */
    public function disassemble(User $user, Stable $stable)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a list of stables.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewList(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a profile for a stable.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Stable  $stable
     * @return bool
     */
    public function view(User $user, Stable $stable)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }
}
