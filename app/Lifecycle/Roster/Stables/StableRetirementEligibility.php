<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Stables;

use App\Exceptions\Roster\Stables\CannotBeRetiredException;
use App\Exceptions\Roster\Stables\CannotBeUnretiredException;
use App\Lifecycle\Concerns\AnswersEligibility;
use App\Models\Roster\Stables\Stable;

final readonly class StableRetirementEligibility
{
    use AnswersEligibility;

    public function __construct(private StableFormerMemberEligibility $formerMemberEligibility) {}

    public function canRetire(Stable $stable): bool
    {
        return $this->passes(fn () => $this->ensureCanRetire($stable), CannotBeRetiredException::class);
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
        return $this->passes(fn () => $this->ensureCanUnretire($stable), CannotBeUnretiredException::class);
    }

    public function ensureCanUnretire(Stable $stable): void
    {
        if ($stable->trashed()) {
            throw CannotBeUnretiredException::deleted($stable);
        }

        if (! $stable->hasCurrentRetirement()) {
            throw CannotBeUnretiredException::notRetired($stable);
        }

        // A stable that was already disbanded (or never active) comes back as it was, with no members to count.
        if (! $this->formerMemberEligibility->wasActiveWhenRetired($stable)) {
            return;
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
