<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Exceptions\Roster\Stables\CannotBeDeletedException;
use App\Exceptions\Roster\Stables\CannotBeRestoredException;
use App\Models\Stables\Stable;
use App\Services\StableMembershipService;

final class StableDeletionEligibility
{
    public function __construct(private readonly StableMembershipService $membershipService) {}

    public function canDelete(Stable $stable): bool
    {
        try {
            $this->ensureCanDelete($stable);

            return true;
        } catch (CannotBeDeletedException) {
            return false;
        }
    }

    public function ensureCanDelete(Stable $stable): void
    {
        if ($stable->trashed()) {
            throw CannotBeDeletedException::alreadyDeleted($stable);
        }

        if ($stable->isCurrentlyActive()) {
            throw CannotBeDeletedException::currentlyActive($stable);
        }

        if ($stable->hasFutureEstablishment()) {
            throw CannotBeDeletedException::futureEstablishmentScheduled($stable);
        }

        $currentMembers = $this->membershipService->currentMembers($stable);

        if ($currentMembers->isNotEmpty()) {
            throw CannotBeDeletedException::hasCurrentMembers(
                $stable,
                $currentMembers->getTotalMemberCount(),
            );
        }
    }

    public function canRestore(Stable $stable): bool
    {
        try {
            $this->ensureCanRestore($stable);

            return true;
        } catch (CannotBeRestoredException) {
            return false;
        }
    }

    public function ensureCanRestore(Stable $stable): void
    {
        if (! $stable->trashed()) {
            throw CannotBeRestoredException::notDeleted($stable);
        }

        $conflictingStable = Stable::query()
            ->where('name', $stable->name)
            ->whereKeyNot($stable->getKey())
            ->first();

        if ($conflictingStable) {
            throw CannotBeRestoredException::nameConflict($stable, $conflictingStable->name);
        }
    }
}
