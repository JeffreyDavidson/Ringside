<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Stables;

use App\Data\Stables\StableMembershipData;
use App\Enums\Stables\StableActivityTransition;
use App\Exceptions\BaseBusinessException;
use App\Exceptions\Roster\Stables\CannotBeDisbandedException;
use App\Exceptions\Roster\Stables\CannotBeEstablishedException;
use App\Exceptions\Roster\Stables\CannotBeReunitedException;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Roster\Stables\StableMembershipService;

final readonly class StableActivityEligibility
{
    public function __construct(
        private StableFormerMemberEligibility $formerMemberEligibility,
        private StableMembershipService $membershipService,
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

    /**
     * A stable holds current members while it is active or scheduled, and while it has never been
     * established and is still being assembled; a disbanded or retired stable holds none.
     */
    public function canHaveMembers(Stable $stable): bool
    {
        return ! $stable->hasActivityHistory()
            || $stable->hasCurrentActivityPeriod()
            || $stable->hasFutureActivityPeriod();
    }

    public function ensureAllowed(Stable $stable, StableActivityTransition $transition): void
    {
        match ($transition) {
            StableActivityTransition::Establish => $this->ensureCanEstablish($stable),
            StableActivityTransition::Disband => $this->ensureCanDisband($stable),
            StableActivityTransition::Reunite => $this->ensureCanReunite($stable),
        };
    }

    /**
     * Every returning member must be an available former member of the stable, and together they must meet the
     * minimum headcount a stable needs to be active.
     */
    public function ensureReturningMembersAllowed(Stable $stable, StableMembershipData $returningMembers): void
    {
        $available = $this->formerMemberEligibility->availableMembersFor($stable);

        $availableWrestlerIds = $available->wrestlers?->pluck('id')->all() ?? [];
        $availableTagTeamIds = $available->tagTeams?->pluck('id')->all() ?? [];

        $notAvailableNames = collect([
            ...$returningMembers->wrestlers?->reject(fn (Wrestler $wrestler): bool => in_array($wrestler->getKey(), $availableWrestlerIds, true)) ?? [],
            ...$returningMembers->tagTeams?->reject(fn (TagTeam $tagTeam): bool => in_array($tagTeam->getKey(), $availableTagTeamIds, true)) ?? [],
        ])->map(fn (Wrestler|TagTeam $member): string => $member->name)->values()->all();

        if ($notAvailableNames !== []) {
            throw CannotBeReunitedException::membersNotAvailable($stable, $notAvailableNames);
        }

        $headcount = $returningMembers->getTotalMemberCount();

        if (! StableMembershipRequirements::hasMinimumHeadcount($headcount)) {
            throw CannotBeReunitedException::belowMinimum($stable, $headcount, StableMembershipRequirements::MINIMUM_MEMBER_COUNT);
        }
    }

    private function ensureCanEstablish(Stable $stable): void
    {
        if ($stable->trashed()) {
            throw CannotBeEstablishedException::deleted($stable);
        }

        if ($stable->hasActivityHistory()) {
            throw CannotBeEstablishedException::established($stable);
        }

        $members = $this->membershipService->currentMembers($stable);

        $memberCount = $members->getTotalMemberCount();

        if (! StableMembershipRequirements::hasMinimumHeadcount($memberCount)) {
            throw CannotBeEstablishedException::insufficientMembers(
                $stable,
                $memberCount,
                StableMembershipRequirements::MINIMUM_MEMBER_COUNT,
            );
        }
    }

    private function ensureCanDisband(Stable $stable): void
    {
        if ($stable->trashed()) {
            throw CannotBeDisbandedException::deleted($stable);
        }

        if (! $stable->hasActivityHistory()) {
            throw CannotBeDisbandedException::unactivated($stable);
        }

        if ($stable->hasFutureActivityPeriod()) {
            throw CannotBeDisbandedException::hasFutureActivation($stable);
        }

        if (! $stable->hasCurrentActivityPeriod()) {
            throw CannotBeDisbandedException::disbanded($stable);
        }
    }

    private function ensureCanReunite(Stable $stable): void
    {
        if ($stable->trashed()) {
            throw CannotBeReunitedException::deleted($stable);
        }

        if (! $stable->hasActivityHistory()) {
            throw CannotBeReunitedException::neverActive($stable);
        }

        if ($stable->hasCurrentActivityPeriod() || $stable->hasFutureActivityPeriod()) {
            throw CannotBeReunitedException::currentlyActive($stable);
        }

        if ($stable->hasCurrentRetirement()) {
            throw CannotBeReunitedException::retired($stable);
        }

        // Only the members who return must be available: an unavailable former member is simply not offered.
        $availableHeadcount = $this->formerMemberEligibility->availableMembersFor($stable)->getTotalMemberCount();
        if (! StableMembershipRequirements::hasMinimumHeadcount($availableHeadcount)) {
            throw CannotBeReunitedException::insufficientFormerMembers(
                $stable,
                $availableHeadcount,
                StableMembershipRequirements::MINIMUM_MEMBER_COUNT,
            );
        }
    }
}
