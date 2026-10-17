<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Roster\Stables\Stable;
use App\Models\Users\User;

/**
 * Stub: PromotionGate (Gate::before) decides every ability on this model from the user's
 * promotion membership role, so these methods only run when the gate falls through and deny.
 */
class StablePolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Stable $stable): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Stable $stable): bool
    {
        return false;
    }

    public function delete(User $user, Stable $stable): bool
    {
        return false;
    }

    public function restore(User $user, Stable $stable): bool
    {
        return false;
    }

    public function establish(User $user, Stable $stable): bool
    {
        return false;
    }

    public function disband(User $user, Stable $stable): bool
    {
        return false;
    }

    public function retire(User $user, Stable $stable): bool
    {
        return false;
    }

    public function unretire(User $user, Stable $stable): bool
    {
        return false;
    }

    public function merge(User $user, Stable $stable): bool
    {
        return false;
    }

    public function split(User $user, Stable $stable): bool
    {
        return false;
    }

    public function reunite(User $user, Stable $stable): bool
    {
        return false;
    }
}
