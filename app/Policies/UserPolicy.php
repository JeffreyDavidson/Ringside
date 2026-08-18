<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Users\User;

/**
 * Simplified UserPolicy using before hook pattern.
 *
 * All repetitive administrator checks are handled by the before hook.
 * Business validation is handled in Actions using custom exceptions.
 */
class UserPolicy
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
    public function view(User $user, User $targetUser): bool
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
    public function update(User $user, User $targetUser): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can delete entities (handled by before hook).
     */
    public function delete(User $user, User $targetUser): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can restore entities (handled by before hook).
     */
    public function restore(User $user, User $targetUser): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can manage users (handled by before hook).
     */
    public function manageUsers(User $user): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can change user roles (handled by before hook).
     */
    public function changeUserRoles(User $user): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }

    /**
     * Only administrators can view audit logs (handled by before hook).
     */
    public function viewAuditLogs(User $user): bool
    {
        return false; // Will be bypassed by before hook for administrators
    }
}
