<?php

namespace App\Policies;

use App\User;
use App\Manager;
use Illuminate\Auth\Access\HandlesAuthorization;

class ManagerPolicy
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
     * Determine whether the user can update a manager.
     *
     * @param  App\User  $user
     * @return bool
     */
    public function update(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can delete a manager.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function delete(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can restore a deleted manager.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function restore(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can retire a manager.
     *
     * @param  \App\User  $user
     * @param  \App\Manager  $manager
     * @return bool
     */
    public function retire(User $user, Manager $manager)
    {
        return $user->isAdministrator() && !$manager->isRetired();
    }

    /**
     * Determine whether the user can unretire a retired manager.
     *
     * @param  \App\User  $user
     * @param  \App\Manager  $manager
     * @return bool
     */
    public function unretire(User $user, Manager $manager)
    {
        return $user->isAdministrator() && $manager->isRetired();
    }

    /**
     * Determine whether the user can suspend a tag team.
     *
     * @param  \App\User  $user
     * @param  \App\Manager  $manager
     * @return bool
     */
    public function suspend(User $user, Manager $manager)
    {
        return $user->isAdministrator() && ! $manager->isSuspended();
    }

    /**
     * Determine whether the user can suspend a tag team.
     *
     * @param  \App\User  $user
     * @param  \App\Manager  $manager
     * @return bool
     */
    public function reinstate(User $user, Manager $manager)
    {
        return $user->isAdministrator() && $manager->isSuspended();
    }

    /**
     * Determine whether the user can injure a manager.
     *
     * @param  \App\User  $user
     * @param  \App\Manager  $manager
     * @return bool
     */
    public function injure(User $user, Manager $manager)
    {
        return $user->isAdministrator() && !$manager->isInjured();
    }

    /**
     * Determine whether the user can recover an injured manager.
     *
     * @param  \App\User  $user
     * @param  \App\Manager  $manager
     * @return bool
     */
    public function recover(User $user, Manager $manager)
    {
        return $user->isAdministrator() && $manager->isInjured();
    }

    /**
     * Determine whether the user can deactivate an active manager.
     *
     * @param  \App\User  $user
     * @param  \App\Manager  $manager
     * @return bool
     */
    public function deactivate(User $user, Manager $manager)
    {
        return $user->isAdministrator() && $manager->isActive();
    }

    /**
     * Determine whether the user can activate an inactive manager.
     *
     * @param  \App\User  $user
     * @param  \App\Manager  $manager
     * @return bool
     */
    public function activate(User $user, Manager $manager)
    {
        return $user->isAdministrator() && ! $manager->isActive();
    }

    /**
     * Determine whether the user can view a list of managers.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function viewList(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a profile for a manager.
     *
     * @param  \App\User  $user
     * @param  \App\Manager  $manager
     * @return bool
     */
    public function view(User $user, Manager $manager)
    {
        return $user->isAdministrator() || $manager->user->is($user);
    }
}
