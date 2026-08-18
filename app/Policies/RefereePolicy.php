<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Roster\Referees\Referee;
use App\Models\Users\User;

/**
 * Simplified RefereePolicy using before hook pattern.
 *
 * All repetitive administrator checks are handled by the before hook.
 * Business validation is handled in Actions using custom exceptions.
 */
class RefereePolicy
{
    /**
     * Administrator bypass for all actions.
     *
     * This before hook allows administrators to perform any action without
     * further permission checks, dramatically simplifying policy logic.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role->isAdministrator()) {
            return true;
        }

        return null; // Continue to individual method checks
    }

    /**
     * Only administrators can view entity lists (handled by before hook).
     */
    public function viewAny(User $user): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can view individual entities (handled by before hook).
     */
    public function view(User $user, Referee $referee): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can create entities (handled by before hook).
     */
    public function create(User $user): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can update entities (handled by before hook).
     */
    public function update(User $user, Referee $referee): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can delete entities (handled by before hook).
     */
    public function delete(User $user, Referee $referee): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can restore entities (handled by before hook).
     */
    public function restore(User $user, Referee $referee): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can employ referees (handled by before hook).
     */
    public function employ(User $user, Referee $referee): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can release referees (handled by before hook).
     */
    public function release(User $user, Referee $referee): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can retire referees (handled by before hook).
     */
    public function retire(User $user, Referee $referee): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can unretire referees (handled by before hook).
     */
    public function unretire(User $user, Referee $referee): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can suspend referees (handled by before hook).
     */
    public function suspend(User $user, Referee $referee): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can reinstate referees (handled by before hook).
     */
    public function reinstate(User $user, Referee $referee): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can injure referees (handled by before hook).
     */
    public function injure(User $user, Referee $referee): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can clear referees from injury (handled by before hook).
     */
    public function clearFromInjury(User $user, Referee $referee): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }
}
