<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Data\Stables\StableMembershipData;
use App\Enums\Stables\StableActivityTransition;
use App\Exceptions\BaseBusinessException;
use App\Exceptions\Roster\Stables\CannotBeDisbandedException;
use App\Exceptions\Roster\Stables\CannotBeEstablishedException;
use App\Exceptions\Roster\Stables\CannotBeReunitedException;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\StableMembershipService;

final class StableActivityEligibility
{
    public function __construct(
        private readonly StableFormerMemberEligibility $formerMemberEligibility,
        private readonly StableMembershipService $membershipService,
    ) {}

    public function allows(Stable $stable, StableActivityTransition $transition): bool
    {
        try {
            $this->ensureAllowed($stable, $transition);

            return true;
        } catch (BaseBusinessException) {
            return false;
        }
    }

    public function ensureAllowed(Stable $stable, StableActivityTransition $transition): void
    {
        match ($transition) {
            StableActivityTransition::Establish => $this->ensureCanEstablish($stable),
            StableActivityTransition::Disband => $this->ensureCanDisband($stable),
            StableActivityTransition::Reunite => $this->ensureCanReunite($stable),
        };
    }

    private function ensureCanEstablish(Stable $stable): void
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

        $members = $this->membershipService->currentMembers($stable);

        if (! $members->hasMinimumMembers()) {
            throw CannotBeEstablishedException::insufficientMembers(
                $stable,
                $members->getTotalMemberCount(),
                StableMembershipData::MINIMUM_MEMBER_COUNT,
            );
        }
    }

    private function ensureCanDisband(Stable $stable): void
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

    private function ensureCanReunite(Stable $stable): void
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
            $unavailableMemberNames = $unavailableKeyMembers
                ->map(fn (Wrestler|TagTeam $member): string => $member->name)
                ->implode(', ');

            throw CannotBeReunitedException::keyMembersUnavailable(
                $stable,
                $unavailableMemberNames,
            );
        }
    }
}
