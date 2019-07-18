<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TagTeam;
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
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can update a tag team.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function update(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can delete a tag team.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function delete(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can restore a deleted tag team.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function restore(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can suspend a tag team.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TagTeam  $tagteam
     * @return bool
     */
    public function suspend(User $user, TagTeam $tagteam)
    {
        if ($tagteam->is_suspended) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can suspend a tag team.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TagTeam  $tagteam
     * @return bool
     */
    public function reinstate(User $user, TagTeam $tagteam)
    {
        if (!$tagteam->is_suspended) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can activate an inactive tag team.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TagTeam  $tagteam
     * @return bool
     */
    public function activate(User $user, TagTeam $tagteam)
    {
        if ($tagteam->is_bookable) {
            return false;
        }
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can retire a tag team.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TagTeam  $tagteam
     * @return bool
     */
    public function retire(User $user, TagTeam $tagteam)
    {
        if ($tagteam->is_retired) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can unretire a retired tag team.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TagTeam  $tagteam
     * @return bool
     */
    public function unretire(User $user, TagTeam $tagteam)
    {
        if (!$tagteam->is_retired) {
            return false;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can view active tag teams.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewList(User $user)
    {
        return $user->isSuperAdministrator() || $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a profile for a tag team.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TagTeam  $tagteam
     * @return bool
     */
    public function view(User $user, TagTeam $tagteam)
    {
        if (!is_null($tagteam->user) && $tagteam->user->is($user)) {
            return true;
        }

        return $user->isSuperAdministrator() || $user->isAdministrator();
    }
}
