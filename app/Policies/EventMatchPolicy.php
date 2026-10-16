<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Matches\EventMatch;
use App\Models\Users\User;

/**
 * Stub: PromotionGate (Gate::before) decides every ability on this model from the user's
 * promotion membership role, so these methods only run when the gate falls through and deny.
 */
class EventMatchPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, EventMatch $eventMatch): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, EventMatch $eventMatch): bool
    {
        return false;
    }

    public function delete(User $user, EventMatch $eventMatch): bool
    {
        return false;
    }

    public function restore(User $user, EventMatch $eventMatch): bool
    {
        return false;
    }
}
