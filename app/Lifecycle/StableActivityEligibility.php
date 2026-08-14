<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Data\Stables\StableMembershipData;
use App\Exceptions\Roster\Stables\CannotBeDisbandedException;
use App\Exceptions\Roster\Stables\CannotBeEstablishedException;
use App\Exceptions\Roster\Stables\CannotBeReunitedException;
use App\Models\Stables\Stable;

final class StableActivityEligibility
{
    public function __construct(private readonly StableFormerMemberEligibility $formerMemberEligibility) {}

    public function canEstablish(Stable $stable): bool
    {
        try {
            $this->ensureCanEstablish($stable);

            return true;
        } catch (CannotBeEstablishedException) {
            return false;
        }
    }

    public function ensureCanEstablish(Stable $stable): void
    {
        if ($stable->trashed()) {
            throw CannotBeEstablishedException::deleted($stable);
        }

        if ($stable->hasActivityPeriods()) {
            throw CannotBeEstablishedException::established($stable);
        }

        if ($stable->isRetired()) {
            throw CannotBeEstablishedException::retired($stable);
        }
    }

    public function canDisband(Stable $stable): bool
    {
        try {
            $this->ensureCanDisband($stable);

            return true;
        } catch (CannotBeDisbandedException) {
            return false;
        }
    }

    public function ensureCanDisband(Stable $stable): void
    {
        if ($stable->trashed()) {
            throw CannotBeDisbandedException::deleted($stable);
        }

        if (! $stable->hasActivityPeriods()) {
            throw CannotBeDisbandedException::unactivated($stable);
        }

        if ($stable->hasFutureActivity()) {
            throw CannotBeDisbandedException::hasFutureActivation($stable);
        }

        if (! $stable->isCurrentlyActive()) {
            throw CannotBeDisbandedException::disbanded($stable);
        }

        if ($stable->isRetired()) {
            throw CannotBeDisbandedException::retired($stable);
        }
    }

    public function canReunite(Stable $stable): bool
    {
        try {
            $this->ensureCanReunite($stable);

            return true;
        } catch (CannotBeReunitedException) {
            return false;
        }
    }

    public function ensureCanReunite(Stable $stable): void
    {
        if ($stable->trashed()) {
            throw CannotBeReunitedException::deleted($stable);
        }

        if (! $stable->hasActivityPeriods()) {
            throw CannotBeReunitedException::neverActive($stable);
        }

        if ($stable->isCurrentlyActive() || $stable->hasFutureActivity()) {
            throw CannotBeReunitedException::currentlyActive($stable);
        }

        if ($stable->isRetired()) {
            throw CannotBeReunitedException::retired($stable);
        }

        $availableFormerMembers = $this->formerMemberEligibility->availableFor($stable);
        if ($availableFormerMembers->count() < StableMembershipData::MINIMUM_MEMBER_COUNT) {
            throw CannotBeReunitedException::insufficientFormerMembers(
                $stable,
                $availableFormerMembers->count(),
                StableMembershipData::MINIMUM_MEMBER_COUNT,
            );
        }

        $unavailableKeyMembers = $this->formerMemberEligibility->unavailableKeyMembersFor($stable);
        if ($unavailableKeyMembers->isNotEmpty()) {
            throw CannotBeReunitedException::keyMembersUnavailable(
                $stable,
                $unavailableKeyMembers->pluck('name')->join(', '),
            );
        }
    }
}
