<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Users\User;

/**
 * Simplified UserPolicy using global Gate hook.
 *
 * All repetitive administrator checks are handled by the global Gate hook.
 * Business validation is handled in Actions using custom exceptions.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, User $targetUser): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, User $targetUser): bool
    {
        return false;
    }

    public function delete(User $user, User $targetUser): bool
    {
        return false;
    }

    public function restore(User $user, User $targetUser): bool
    {
        return false;
    }

    public function manageUsers(User $user): bool
    {
        return false;
    }

    public function changeUserRoles(User $user): bool
    {
        return false;
    }

    public function viewAuditLogs(User $user): bool
    {
        return false;
    }
}
