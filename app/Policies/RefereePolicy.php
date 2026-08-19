<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Roster\Referees\Referee;
use App\Models\Users\User;

/**
 * Simplified RefereePolicy using global Gate hook.
 *
 * All repetitive administrator checks are handled by the global Gate hook.
 * Business validation is handled in Actions using custom exceptions.
 */
class RefereePolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Referee $referee): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Referee $referee): bool
    {
        return false;
    }

    public function delete(User $user, Referee $referee): bool
    {
        return false;
    }

    public function restore(User $user, Referee $referee): bool
    {
        return false;
    }

    public function employ(User $user, Referee $referee): bool
    {
        return false;
    }

    public function release(User $user, Referee $referee): bool
    {
        return false;
    }

    public function retire(User $user, Referee $referee): bool
    {
        return false;
    }

    public function unretire(User $user, Referee $referee): bool
    {
        return false;
    }

    public function suspend(User $user, Referee $referee): bool
    {
        return false;
    }

    public function reinstate(User $user, Referee $referee): bool
    {
        return false;
    }

    public function injure(User $user, Referee $referee): bool
    {
        return false;
    }

    public function clearFromInjury(User $user, Referee $referee): bool
    {
        return false;
    }
}
