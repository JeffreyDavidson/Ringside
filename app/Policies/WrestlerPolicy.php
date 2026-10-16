<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;

/**
 * Stub: PromotionGate (Gate::before) decides every ability on this model from the user's
 * promotion membership role, so these methods only run when the gate falls through and deny.
 */
class WrestlerPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Wrestler $wrestler): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Wrestler $wrestler): bool
    {
        return false;
    }

    public function delete(User $user, Wrestler $wrestler): bool
    {
        return false;
    }

    public function restore(User $user, Wrestler $wrestler): bool
    {
        return false;
    }

    public function employ(User $user, Wrestler $wrestler): bool
    {
        return false;
    }

    public function release(User $user, Wrestler $wrestler): bool
    {
        return false;
    }

    public function retire(User $user, Wrestler $wrestler): bool
    {
        return false;
    }

    public function unretire(User $user, Wrestler $wrestler): bool
    {
        return false;
    }

    public function suspend(User $user, Wrestler $wrestler): bool
    {
        return false;
    }

    public function reinstate(User $user, Wrestler $wrestler): bool
    {
        return false;
    }

    public function injure(User $user, Wrestler $wrestler): bool
    {
        return false;
    }

    public function clearFromInjury(User $user, Wrestler $wrestler): bool
    {
        return false;
    }
}
