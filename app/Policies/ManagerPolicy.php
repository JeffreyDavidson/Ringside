<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Manager;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ManagerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create managers.
     */
    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can update a manager.
     */
    public function update(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can delete a manager.
     */
    public function delete(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can restore a manager.
     */
    public function restore(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can retire a manager.
     */
    public function retire(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can unretire a manager.
     */
    public function unretire(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can suspend a manager.
     */
    public function suspend(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can reinstate a manager.
     */
    public function reinstate(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can injure a manager.
     */
    public function injure(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can recover a manager.
     */
    public function clearFromInjury(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can employ a manager.
     */
    public function employ(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can release a manager.
     */
    public function release(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a list of managers.
     */
    public function viewList(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a profile for a manager.
     */
    public function view(User $user, Manager $manager): bool
    {
        if ($manager->user !== null && $manager->user->is($user)) {
            return true;
        }

        return $user->isAdministrator();
    }
}
