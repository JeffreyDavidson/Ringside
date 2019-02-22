<?php

namespace App\Policies;

use App\User;
use App\TagTeam;
use Illuminate\Auth\Access\HandlesAuthorization;

class TagTeamPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create wrestlers.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can update a tag team.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function update(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can delete a tag team.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function delete(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can restore a deleted tag team.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function restore(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can suspend a tag team.
     *
     * @param  \App\User  $user
     * @param  \App\TagTeam  $tagteam
     * @return bool
     */
    public function suspend(User $user, TagTeam $tagteam)
    {
        return $user->isAdministrator() && !$tagteam->isSuspended();
    }

    /**
     * Determine whether the user can suspend a tag team.
     *
     * @param  \App\User  $user
     * @param  \App\TagTeam  $tagteam
     * @return bool
     */
    public function reinstate(User $user, TagTeam $tagteam)
    {
        return $user->isAdministrator() && $tagteam->isSuspended();
    }

    /**
     * Determine whether the user can deactivate an active tag team.
     *
     * @param  \App\User  $user
     * @param  \App\TagTeam  $tagteam
     * @return bool
     */
    public function deactivate(User $user, TagTeam $tagteam)
    {
        return $user->isAdministrator() && $tagteam->isActive();
    }

    /**
     * Determine whether the user can activate an inactive tag team.
     *
     * @param  \App\User  $user
     * @param  \App\TagTeam  $tagteam
     * @return bool
     */
    public function activate(User $user, TagTeam $tagteam)
    {
        return $user->isAdministrator() && ! $tagteam->isActive();
    }

    /**
     * Determine whether the user can retire a tag team.
     *
     * @param  \App\User  $user
     * @param  \App\TagTeam  $tagteam
     * @return bool
     */
    public function retire(User $user, TagTeam $tagteam)
    {
        return $user->isAdministrator() && ! $tagteam->isRetired();
    }

    /**
     * Determine whether the user can unretire a retired tag team.
     *
     * @param  \App\User  $user
     * @param  \App\TagTeam  $tagteam
     * @return bool
     */
    public function unretire(User $user, TagTeam $tagteam)
    {
        return $user->isAdministrator() && $tagteam->isRetired();
    }

    /**
     * Determine whether the user can view active tag teams.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function viewList(User $user)
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a profile for a tag team.
     *
     * @param  \App\User  $user
     * @param  \App\TagTeam  $tagteam
     * @return bool
     */
    public function view(User $user, TagTeam $tagteam)
    {
        return $user->isAdministrator() || $tagteam->user->is($user);
    }
}
