<?php

declare(strict_types=1);

namespace App\Lifecycle\Venues;

use App\Exceptions\Events\CannotBeRestoredException;
use App\Models\Events\Venue;

final class VenueDeletionEligibility
{
    public function ensureCanRestore(Venue $venue): void
    {
        if (! $venue->trashed()) {
            throw CannotBeRestoredException::notDeleted($venue);
        }

        $conflictingVenue = Venue::query()
            ->whereName($venue->name)
            ->whereKeyNot($venue->getKey())
            ->first();

        if ($conflictingVenue !== null) {
            throw CannotBeRestoredException::nameConflict($venue, $conflictingVenue->name);
        }
    }
}
