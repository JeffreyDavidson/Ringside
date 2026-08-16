<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Data\Stables\StableMembershipData;
use App\Exceptions\Roster\Stables\CannotBeRetiredException;
use App\Exceptions\Roster\Stables\CannotBeUnretiredException;
use App\Models\Roster\Stables\Stable;
use Illuminate\Database\Eloquent\Builder;

final class StableRetirementEligibility
{
    public function __construct(private readonly StableFormerMemberEligibility $formerMemberEligibility) {}

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

        if ($stable->isRetired()) {
            throw CannotBeRetiredException::alreadyRetired($stable);
        }

        if (! $stable->hasActivityPeriods() || (! $stable->isCurrentlyActive() && $stable->hasFutureActivity())) {
            throw CannotBeRetiredException::notActive($stable);
        }
    }

    public function canUnretire(Stable $stable, bool $requireFormerMembers = true): bool
    {
        try {
            $this->ensureCanUnretire($stable, $requireFormerMembers);

            return true;
        } catch (CannotBeUnretiredException) {
            return false;
        }
    }

    public function ensureCanUnretire(Stable $stable, bool $requireFormerMembers = true): void
    {
        if ($stable->trashed()) {
            throw CannotBeUnretiredException::deleted($stable);
        }

        if (! $stable->isRetired()) {
            throw CannotBeUnretiredException::notRetired($stable);
        }

        $conflictingStable = Stable::query()
            ->where('name', $stable->name)
            ->whereKeyNot($stable->getKey())
            ->whereHas('activityPeriods', fn (Builder $query): Builder => $query->whereNull('ended_at'))
            ->first();

        if ($conflictingStable) {
            throw CannotBeUnretiredException::nameConflict($stable, $conflictingStable->name);
        }

        if (! $requireFormerMembers) {
            return;
        }

        $availableFormerMembers = $this->formerMemberEligibility->availableFor($stable);

        if ($availableFormerMembers->isEmpty()) {
            throw CannotBeUnretiredException::noAvailableFormerMembers($stable);
        }

        if ($availableFormerMembers->count() < StableMembershipData::MINIMUM_MEMBER_COUNT) {
            throw CannotBeUnretiredException::insufficientFormerMembers(
                $stable,
                $availableFormerMembers->count(),
                StableMembershipData::MINIMUM_MEMBER_COUNT,
            );
        }

        $unavailableKeyMembers = $this->formerMemberEligibility->unavailableKeyMembersFor($stable);

        if ($unavailableKeyMembers->isNotEmpty()) {
            throw CannotBeUnretiredException::keyMembersUnavailable(
                $stable,
                $unavailableKeyMembers->pluck('name')->join(', '),
            );
        }
    }
}
