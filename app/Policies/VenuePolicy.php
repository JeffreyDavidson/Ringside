<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class VenuePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create venues.
     */
    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can update a venue.
     */
    public function update(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can delete a venue.
     */
    public function delete(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can restore a venue.
     */
    public function restore(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view all venues.
     */
    public function viewList(User $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user can view a venue.
     */
    public function view(User $user): bool
    {
        return $user->isAdministrator();
    }
}
