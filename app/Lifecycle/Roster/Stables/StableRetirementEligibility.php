<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Stables;

use App\Exceptions\Roster\Stables\CannotBeRetiredException;
use App\Exceptions\Roster\Stables\CannotBeUnretiredException;
use App\Models\Roster\Stables\Stable;

final readonly class StableRetirementEligibility
{
    public function __construct(private StableFormerMemberEligibility $formerMemberEligibility) {}

    public function canRetire(Stable $stable): bool
    {
        try {
            $this->ensureCanRetire($stable);

            return true;
        } catch (CannotBeRetiredException) {
            return false;
        }
    }

    public function ensureCanRetire(Stable $stable): void
    {
        if ($stable->trashed()) {
            throw CannotBeRetiredException::deleted($stable);
        }

        if ($stable->hasCurrentRetirement()) {
            throw CannotBeRetiredException::alreadyRetired($stable);
        }

        if (! $stable->hasActivityHistory() || (! $stable->hasCurrentActivityPeriod() && $stable->hasFutureActivityPeriod())) {
            throw CannotBeRetiredException::notActive($stable);
        }
    }

    public function canUnretire(Stable $stable): bool
    {
        try {
            $this->ensureCanUnretire($stable);

            return true;
        } catch (CannotBeUnretiredException) {
            return false;
        }
    }

    public function ensureCanUnretire(Stable $stable): void
    {
        if ($stable->trashed()) {
            throw CannotBeUnretiredException::deleted($stable);
        }

        if (! $stable->hasCurrentRetirement()) {
            throw CannotBeUnretiredException::notRetired($stable);
        }

        $availableHeadcount = $this->formerMemberEligibility->availableMembersAtRetirementFor($stable)->getTotalMemberCount();

        if (! StableMembershipRequirements::hasMinimumHeadcount($availableHeadcount)) {
            throw CannotBeUnretiredException::insufficientFormerMembers(
                $stable,
                $availableHeadcount,
                StableMembershipRequirements::MINIMUM_MEMBER_COUNT,
            );
        }
    }
}
