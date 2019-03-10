<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Stable;
use Illuminate\Auth\Access\HandlesAuthorization;

class StablePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create managers.
     *
     * @param  App\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can update a stable.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function update(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can delete a stable.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function delete(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can restore a deleted stable.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function restore(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can suspend a stable.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Stable  $stable
     * @return bool
     */
    public function suspend(User $user, Stable $stable)
    {
        return $user->isAdministrator() && !$stable->isSuspended();
    }

    /**
     * Determine whether the user can suspend a stable.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Stable  $stable
     * @return bool
     */
    public function reinstate(User $user, Stable $stable)
    {
        return $user->isAdministrator() && $stable->isSuspended();
    }

    /**
     * Determine whether the user can deactivate an active stable.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Stable  $stable
     * @return bool
     */
    public function deactivate(User $user, Stable $stable)
    {
        return $user->isAdministrator() && $stable->isActive();
    }

    /**
     * Determine whether the user can activate an inactive stable.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Stable  $stable
     * @return bool
     */
    public function activate(User $user, Stable $stable)
    {
        return $user->isAdministrator() && !$stable->isActive();
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
        return $user->isAdministrator() && !$stable->isRetired();
    }

    /**
     * Determine whether the user can unretire a retired stable.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Stable  $stable
     * @return bool
     */
    public function unretire(User $user, Stable $stable)
    {
        return $user->isAdministrator() && $stable->isRetired();
    }

    /**
     * Determine whether the user can view a list of stables.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewList(User $user)
    {
        return $user->isAdministrator();
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
        return $user->isAdministrator() || $stable->user->is($user);
    }
}
