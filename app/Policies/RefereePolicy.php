<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RefereePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create referees.
     *
     * @param  App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can update a referee.
     *
     * @param  App\Models\User  $user
     * @return bool
     */
    public function update(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can delete a referee.
     *
     * @param  App\Models\User  $user
     * @return bool
     */
    public function delete(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can restore a referee.
     *
     * @param  App\Models\User  $user
     * @return bool
     */
    public function restore(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can retire a referee.
     *
     * @param  App\Models\User  $user
     * @return bool
     */
    public function retire(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can unretire a referee.
     *
     * @param  App\Models\User  $user
     * @return bool
     */
    public function unretire(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can injure a referee.
     *
     * @param  App\Models\User  $user
     * @return bool
     */
    public function injure(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can recover a referee.
     *
     * @param  App\Models\User  $user
     * @return bool
     */
    public function clearFromInjury(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can suspend a referee.
     *
     * @param  App\Models\User  $user
     * @return bool
     */
    public function suspend(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can reinstate a referee.
     *
     * @param  App\Models\User  $user
     * @return bool
     */
    public function reinstate(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can employ a referee.
     *
     * @param  App\Models\User  $user
     * @return bool
     */
    public function employ(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a list of referees.
     *
     * @param  App\Models\User  $user
     * @return bool
     */
    public function viewList(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a profile for a referee.
     *ååå
     * @param  App\Models\User  $user
     * @return bool
     */
    public function view(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }
}
