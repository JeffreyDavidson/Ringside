<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Users\User;

/**
 * Stub: PromotionGate (Gate::before) allows administrators everything. This model is not
 * promotion-owned, so the gate falls through for every other user and these methods deny.
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
}
