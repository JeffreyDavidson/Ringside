<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wrestler;
use Illuminate\Auth\Access\HandlesAuthorization;

class WrestlerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create wrestlers.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can update a wrestler.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Wrestler  $wrestler
     * @return bool
     */
    public function update(User $user, Wrestler $wrestler)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can delete a wrestler.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Wrestler  $wrestler
     * @return bool
     */
    public function delete(User $user, Wrestler $wrestler)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can restore a deleted wrestler.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function restore(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can retire a wrestler.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Wrestler  $wrestler
     * @return bool
     */
    public function retire(User $user, Wrestler $wrestler)
    {
        if (!$wrestler->is_hired || $wrestler->is_retired) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can unretire a wrestler.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Wrestler  $wrestler
     * @return bool
     */
    public function unretire(User $user, Wrestler $wrestler)
    {
        if (!$wrestler->is_retired) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can suspend a wrestler.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Wrestler  $wrestler
     * @return bool
     */
    public function suspend(User $user, Wrestler $wrestler)
    {
        if (!$wrestler->is_hired || !$wrestler->is_bookable || $wrestler->is_suspended) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can reinstate a wrestler.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Wrestler  $wrestler
     * @return bool
     */
    public function reinstate(User $user, Wrestler $wrestler)
    {
        if (!$wrestler->is_suspended) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can injure a wrestler.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Wrestler  $wrestler
     * @return bool
     */
    public function injure(User $user, Wrestler $wrestler)
    {
        if (!$wrestler->is_hired || !$wrestler->is_bookable || $wrestler->is_injured) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can recover a wrestler.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Wrestler  $wrestler
     * @return bool
     */
    public function recover(User $user, Wrestler $wrestler)
    {
        if (!$wrestler->is_hired || !$wrestler->is_injured) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can activate a pending introduced wrestler.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Wrestler  $wrestler
     * @return bool
     */
    public function activate(User $user, Wrestler $wrestler)
    {
        if ($wrestler->is_hired) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can view active wrestlers.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewList(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a profile for a wrestler.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Wrestler  $wrestler
     * @return bool
     */
    public function view(User $user, Wrestler $wrestler)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }
}
