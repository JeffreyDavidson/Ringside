<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Users\User;

/**
 * Stub: PromotionGate (Gate::before) decides every ability on this model from the user's
 * promotion membership role, so these methods only run when the gate falls through and deny.
 */
class TagTeamPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, TagTeam $tagTeam): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, TagTeam $tagTeam): bool
    {
        return false;
    }

    public function delete(User $user, TagTeam $tagTeam): bool
    {
        return false;
    }

    public function restore(User $user, TagTeam $tagTeam): bool
    {
        return false;
    }

    public function employ(User $user, TagTeam $tagTeam): bool
    {
        return false;
    }

    public function release(User $user, TagTeam $tagTeam): bool
    {
        return false;
    }

    public function suspend(User $user, TagTeam $tagTeam): bool
    {
        return false;
    }

    public function reinstate(User $user, TagTeam $tagTeam): bool
    {
        return false;
    }

    public function retire(User $user, TagTeam $tagTeam): bool
    {
        return false;
    }

    public function unretire(User $user, TagTeam $tagTeam): bool
    {
        return false;
    }
}
