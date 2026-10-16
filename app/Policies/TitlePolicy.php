<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Titles\Title;
use App\Models\Users\User;

/**
 * Stub: PromotionGate (Gate::before) decides every ability on this model from the user's
 * promotion membership role, so these methods only run when the gate falls through and deny.
 */
class TitlePolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Title $title): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Title $title): bool
    {
        return false;
    }

    public function delete(User $user, Title $title): bool
    {
        return false;
    }

    public function restore(User $user, Title $title): bool
    {
        return false;
    }

    public function debut(User $user, Title $title): bool
    {
        return false;
    }

    public function pull(User $user, Title $title): bool
    {
        return false;
    }

    public function reinstate(User $user, Title $title): bool
    {
        return false;
    }

    public function retire(User $user, Title $title): bool
    {
        return false;
    }

    public function unretire(User $user, Title $title): bool
    {
        return false;
    }
}
