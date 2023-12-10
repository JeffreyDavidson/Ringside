<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Wrestler;
use Illuminate\Auth\Access\HandlesAuthorization;

class WrestlerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create wrestlers.
     */
    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can update a wrestler.
     */
    public function update(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can delete a wrestler.
     */
    public function delete(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can restore a wrestler.
     */
    public function restore(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can retire a wrestler.
     */
    public function retire(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can unretire a wrestler.
     */
    public function unretire(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can suspend a wrestler.
     */
    public function suspend(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can reinstate a wrestler.
     */
    public function reinstate(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can injure a wrestler.
     */
    public function injure(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can clear a wrestler from an injury.
     */
    public function clearFromInjury(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can employ a wrestler.
     */
    public function employ(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can release a wrestler.
     */
    public function release(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a list of wrestlers.
     */
    public function viewList(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a profile for a wrestler.
     */
    public function view(User $user, Wrestler $wrestler): bool
    {
        if ($wrestler->user !== null && $wrestler->user->is($user)) {
            return true;
        }

        return $user->isAdministrator();
    }
}
