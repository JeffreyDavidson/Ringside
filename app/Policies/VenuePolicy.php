<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Events\Venue;
use App\Models\Users\User;

/**
 * Stub: PromotionGate (Gate::before) allows administrators everything. This model is not
 * promotion-owned, so the gate falls through for every other user and these methods deny.
 */
class VenuePolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Venue $venue): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Venue $venue): bool
    {
        return false;
    }

    public function delete(User $user, Venue $venue): bool
    {
        return false;
    }

    public function restore(User $user, Venue $venue): bool
    {
        return false;
    }
}
