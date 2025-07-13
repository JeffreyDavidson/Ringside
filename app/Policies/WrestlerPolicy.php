<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Users\User;
use Tests\Unit\Policies\WrestlerPolicyTest;

/**
 * Simplified WrestlerPolicy with business logic moved to Actions.
 *
 * This policy only handles authorization (who can do what), not validation
 * (whether the action is valid). Business rules and entity state validation
 * are handled in the corresponding Actions using custom exceptions.
 *
 * @see WrestlerPolicyTest
 */
class WrestlerPolicy
{
    /**
     * Administrator bypass for all actions.
     *
     * This before hook allows administrators to perform any action without
     * further permission checks, dramatically simplifying policy logic.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdministrator()) {
            return true;
        }

        return null; // Continue to individual method checks
    }

    /**
     * Only administrators can view entity lists (handled by before hook).
     */
    public function viewList(User $user): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can view individual entities (handled by before hook).
     */
    public function view(User $user): bool
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
    public function update(User $user): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can delete entities (handled by before hook).
     */
    public function delete(User $user): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can restore entities (handled by before hook).
     */
    public function restore(User $user): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can employ wrestlers (handled by before hook).
     */
    public function employ(User $user): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can release wrestlers (handled by before hook).
     */
    public function release(User $user): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can retire wrestlers (handled by before hook).
     */
    public function retire(User $user): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can unretire wrestlers (handled by before hook).
     */
    public function unretire(User $user): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can suspend wrestlers (handled by before hook).
     */
    public function suspend(User $user): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can reinstate wrestlers (handled by before hook).
     */
    public function reinstate(User $user): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can injure wrestlers (handled by before hook).
     */
    public function injure(User $user): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can clear wrestlers from injury (handled by before hook).
     */
    public function clearFromInjury(User $user): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }
}
