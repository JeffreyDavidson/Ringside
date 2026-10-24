<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Stables;

use App\Exceptions\Roster\Stables\CannotBeDeletedException;
use App\Exceptions\Roster\Stables\CannotBeRestoredException;
use App\Lifecycle\Concerns\AnswersEligibility;
use App\Models\Roster\Stables\Stable;
use App\Services\Roster\Stables\StableMembershipService;

final readonly class StableDeletionEligibility
{
    use AnswersEligibility;

    public function __construct(private StableMembershipService $membershipService) {}

    public function canDelete(Stable $stable): bool
    {
        return $this->passes(fn () => $this->ensureCanDelete($stable), CannotBeDeletedException::class);
    }

    public function ensureCanDelete(Stable $stable): void
    {
        if ($stable->trashed()) {
            throw CannotBeDeletedException::alreadyDeleted($stable);
        }

        if ($stable->currentActivityPeriod()->exists()) {
            throw CannotBeDeletedException::currentlyActive($stable);
        }

        if ($stable->futureActivityPeriod()->exists()) {
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
        return $this->passes(fn () => $this->ensureCanRestore($stable), CannotBeRestoredException::class);
    }

    public function ensureCanRestore(Stable $stable): void
    {
        if (! $stable->trashed()) {
            throw CannotBeRestoredException::notDeleted($stable);
        }

        $conflictingStable = Stable::query()
            ->whereNameConflictsWith($stable)
            ->first();

        if ($conflictingStable) {
            throw CannotBeRestoredException::nameConflict($stable, $conflictingStable->name);
        }
    }
}
