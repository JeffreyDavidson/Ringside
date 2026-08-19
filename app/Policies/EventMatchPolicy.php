<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Matches\EventMatch;
use App\Models\Users\User;

/**
 * Simplified EventMatchPolicy using global Gate hook.
 *
 * All repetitive administrator checks are handled by the global Gate hook.
 * Business validation is handled in Actions using custom exceptions.
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
