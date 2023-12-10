<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create an event.
     */
    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can update an event.
     */
    public function update(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can delete an event.
     */
    public function delete(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can restore a deleted event.
     */
    public function restore(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a list of events.
     */
    public function viewList(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view an event.
     */
    public function view(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can add matches to an event.
     */
    public function addMatches(User $user): bool
    {
        return $user->isAdministrator();
    }
}
