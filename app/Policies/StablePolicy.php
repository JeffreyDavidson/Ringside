<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Roster\Stables\Stable;
use App\Models\Users\User;

/**
 * Simplified StablePolicy using global Gate hook.
 *
 * All repetitive administrator checks are handled by the global Gate hook.
 * Business validation is handled in Actions using custom exceptions.
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
}
