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
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can update a wrestler.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function update(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can delete a wrestler.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function delete(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can restore a deleted wrestler.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function restore(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can retire a wrestler.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function retire(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can unretire a retired wrestler.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function unretire(User $user)
    {
        return $user->isAdministrator();
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
        return $user->isAdministrator() && !$wrestler->isSuspended();
    }

    /**
     * Determine whether the user can reinstate a suspended wrestler.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Wrestler  $wrestler
     * @return bool
     */
    public function reinstate(User $user, Wrestler $wrestler)
    {
        return $user->isAdministrator() && $wrestler->isSuspended();
    }

    /**
     * Determine whether the user can injure a wrestler.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function injure(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can recover an injured wrestler.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function recover(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can deactivate an active wrestler.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function deactivate(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can activate an inactive wrestler.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function activate(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view active wrestlers.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewList(User $user)
    {
        return $user->isAdministrator();
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
        return $user->isAdministrator() || $wrestler->user->is($user);
    }
}
