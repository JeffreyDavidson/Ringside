<?php

declare(strict_types=1);

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
     * Determine whether the user can restore a wrestler.
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
     * Determine whether the user can unretire a wrestler.
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
     * @return bool
     */
    public function suspend(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can reinstate a wrestler.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function reinstate(User $user)
    {
        return $user->isAdministrator();
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
     * Determine whether the user can clear a wrestler from an injury.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function clearFromInjury(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can employ a wrestler.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function employ(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can release a wrestler.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function release(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a list of wrestlers.
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
        if ($wrestler->user !== null && $wrestler->user->is($user)) {
            return true;
        }

        return $user->isAdministrator();
    }
}
