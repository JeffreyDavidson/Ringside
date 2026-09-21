<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Titles\Title;
use App\Models\Users\User;

/**
 * Simplified TitlePolicy using global Gate hook.
 *
 * All repetitive administrator checks are handled by the global Gate hook.
 * Business validation is handled in Actions using custom exceptions.
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

    public function activate(User $user, Title $title): bool
    {
        return false;
    }

    public function deactivate(User $user, Title $title): bool
    {
        return false;
    }
}
