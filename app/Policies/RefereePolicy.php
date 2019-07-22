<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Referee;
use Illuminate\Auth\Access\HandlesAuthorization;

class RefereePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create referees.
     *
     * @param  \App\Models\User  $user
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
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function delete(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can restore a deleted referee.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function restore(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can retire a referee.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Referee  $referee
     * @return bool
     */
    public function retire(User $user, Referee $referee)
    {
        if ($referee->is_retired) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can unretire a retired referee.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Referee  $referee
     * @return bool
     */
    public function unretire(User $user, Referee $referee)
    {
        if (!$referee->is_retired) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can injure a referee.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Referee  $referee
     * @return bool
     */
    public function injure(User $user, Referee $referee)
    {
        if ($referee->is_injured) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can recover an injured referee.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Referee  $referee
     * @return bool
     */
    public function recover(User $user, Referee $referee)
    {
        if (!$referee->is_injured) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can activate a pending introduced referee.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Referee  $referee
     * @return bool
     */
    public function activate(User $user, Referee $referee)
    {
        if ($referee->is_employed) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can view list of referees.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewList(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a profile for a referee.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Referee  $referee
     * @return bool
     */
    public function view(User $user, Referee $referee)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }
}
