<?php

namespace App\Policies;

use App\Models\Manager;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ManagerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create managers.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can update a manager.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function update(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can delete a manager.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function delete(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can restore a manager.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function restore(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can retire a manager.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function retire(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can unretire a manager.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function unretire(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can suspend a manager.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function suspend(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can reinstate a manager.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function reinstate(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can injure a manager.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function injure(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can recover a manager.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function clearFromInjury(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can employ a manager.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function employ(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can release a manager.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function release(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a list of managers.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewList(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a profile for a manager.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Manager  $manager
     * @return bool
     */
    public function view(User $user, Manager $manager)
    {
        if ($manager->user !== null && $manager->user->is($user)) {
            return true;
        }

        return $user->isAdministrator();
    }
}
