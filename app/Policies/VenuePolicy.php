<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Events\Venue;
use App\Models\Users\User;

/**
 * Simplified VenuePolicy using global Gate hook.
 *
 * All repetitive administrator checks are handled by the global Gate hook.
 * Business validation is handled in Actions using custom exceptions.
 */
class VenuePolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Venue $venue): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Venue $venue): bool
    {
        return false;
    }

    public function delete(User $user, Venue $venue): bool
    {
        return false;
    }

    public function restore(User $user, Venue $venue): bool
    {
        return false;
    }
}
