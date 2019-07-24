<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Manager;
use Illuminate\Auth\Access\HandlesAuthorization;

class ManagerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create managers.
     *
     * @param  App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can update a manager.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Manager  $manager
     * @return bool
     */
    public function update(User $user, Manager $manager)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can delete a manager.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Manager  $manager
     * @return bool
     */
    public function delete(User $user, Manager $manager)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can restore a deleted manager.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Manager  $manager
     * @return bool
     */
    public function restore(User $user, Manager $manager)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can retire a manager.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Manager  $manager
     * @return bool
     */
    public function retire(User $user, Manager $manager)
    {
        if (!$manager->is_employed || $manager->is_retired) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can unretire a retired manager.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Manager  $manager
     * @return bool
     */
    public function unretire(User $user, Manager $manager)
    {
        if (!$manager->is_retired) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can suspend a tag team.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Manager  $manager
     * @return bool
     */
    public function suspend(User $user, Manager $manager)
    {
        if (!$manager->is_employed || !$manager->is_bookable || $manager->is_suspended) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can suspend a tag team.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Manager  $manager
     * @return bool
     */
    public function reinstate(User $user, Manager $manager)
    {
        if (!$manager->is_suspended) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can injure a manager.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Manager  $manager
     * @return bool
     */
    public function injure(User $user, Manager $manager)
    {
        if (!$manager->is_employed || !$manager->is_bookable || $manager->is_injured) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can recover an injured manager.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Manager  $manager
     * @return bool
     */
    public function recover(User $user, Manager $manager)
    {
        if (!$manager->is_employed || !$manager->is_injured) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can activate an inactive manager.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Manager  $manager
     * @return bool
     */
    public function activate(User $user, Manager $manager)
    {
        if ($manager->is_employed) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a list of managers.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewList(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
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
        return $user->isSuperAdministrator() ||  $user->isAdministrator() || $manager->user->is($user);
    }
}
