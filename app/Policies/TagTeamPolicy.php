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
     */
    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can update a tag team.
     */
    public function update(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can delete a tag team.
     */
    public function delete(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can restore a tag team.
     */
    public function restore(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can suspend a tag team.
     */
    public function suspend(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can reinstate a tag team.
     */
    public function reinstate(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can employ a tag team.
     */
    public function employ(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can release a tag team.
     */
    public function release(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can retire a tag team.
     */
    public function retire(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can unretire a tag team.
     */
    public function unretire(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a list of tag teams.
     */
    public function viewList(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a profile for a tag team.
     */
    public function view(User $user, TagTeam $tagTeam): bool
    {
        if ($tagTeam->user !== null && $tagTeam->user->is($user)) {
            return true;
        }

        return $user->isAdministrator();
    }
}
