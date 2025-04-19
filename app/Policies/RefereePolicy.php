<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RefereePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create referees.
     */
    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can update a referee.
     */
    public function update(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can delete a referee.
     */
    public function delete(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can restore a referee.
     */
    public function restore(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can retire a referee.
     */
    public function retire(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can unretire a referee.
     */
    public function unretire(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can injure a referee.
     */
    public function injure(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can recover a referee.
     */
    public function clearFromInjury(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can suspend a referee.
     */
    public function suspend(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can reinstate a referee.
     */
    public function reinstate(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can employ a referee.
     */
    public function employ(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can release a referee.
     */
    public function release(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a list of referees.
     */
    public function viewList(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a profile for a referee.
     */
    public function view(User $user): bool
    {
        return $user->isAdministrator();
    }
}
