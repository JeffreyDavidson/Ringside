<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TagTeam;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TagTeamPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create tag teams.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can update a tag team.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function update(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can delete a tag team.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function delete(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can restore a tag team.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function restore(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can suspend a tag team.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function suspend(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can reinstate a tag team.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function reinstate(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can employ a tag team.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function employ(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can release a tag team.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function release(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can retire a tag team.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function retire(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can unretire a tag team.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function unretire(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a list of tag teams.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewList(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a profile for a tag team.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TagTeam  $tagTeam
     * @return bool
     */
    public function view(User $user, TagTeam $tagTeam)
    {
        if ($tagTeam->user !== null && $tagTeam->user->is($user)) {
            return true;
        }

        return $user->isAdministrator();
    }
}
