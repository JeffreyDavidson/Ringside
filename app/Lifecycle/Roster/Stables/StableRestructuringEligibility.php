<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Stables;

use App\Data\Stables\StableMembershipData;
use App\Enums\Stables\StableMemberUnavailability;
use App\Exceptions\Roster\Stables\CannotBeMergedException;
use App\Exceptions\Roster\Stables\CannotBeSplitException;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Roster\Stables\StableMembershipService;

final readonly class StableRestructuringEligibility
{
    public function __construct(private StableMembershipService $membershipService) {}

    public function canSplit(Stable $stable): bool
    {
        try {
            $this->ensureCanSplit($stable);

            return true;
        } catch (CannotBeSplitException) {
            return false;
        }
    }

    /**
     * Whether the stable could take part in a merge at all; the other stable is only known once one is picked.
     */
    public function canStartMerge(Stable $stable): bool
    {
        return $stable->hasCurrentActivityPeriod() && ! $stable->hasCurrentRetirement();
    }

    public function ensureCanSplit(Stable $stable): void
    {
        if ($stable->hasCurrentRetirement()) {
            throw CannotBeSplitException::retired($stable);
        }

        if (! $stable->hasCurrentActivityPeriod()) {
            throw CannotBeSplitException::notActive($stable);
        }

        $minimumMemberCount = StableMembershipRequirements::MINIMUM_MEMBER_COUNT * 2;
        $currentMemberCount = $this->membershipService->currentMembers($stable)->getTotalMemberCount();

        if ($currentMemberCount < $minimumMemberCount) {
            throw CannotBeSplitException::insufficientMembers($stable, $currentMemberCount, $minimumMemberCount);
        }
    }

    /**
     * The new stable joins the original stable's promotion, so its name must be free among that promotion's active
     * stables (or among the active stables without a promotion). The database enforces the same rule except for
     * stables without a promotion on MySQL, which has no partial index, so it is checked here on every engine.
     */
    public function ensureSplitNameAvailable(Stable $stable, string $name): void
    {
        $nameTaken = Stable::query()
            ->withoutGlobalScope('promotion_context')
            ->where('promotion_id', $stable->promotion_id)
            ->where('name', $name)
            ->exists();

        if ($nameTaken) {
            throw CannotBeSplitException::nameTaken($name);
        }
    }

    public function ensureCanMerge(Stable $primaryStable, Stable $secondaryStable): void
    {
        if ($primaryStable->is($secondaryStable)) {
            throw CannotBeMergedException::selfMerge($primaryStable);
        }

        if ($primaryStable->promotion_id !== $secondaryStable->promotion_id) {
            throw CannotBeMergedException::differentPromotions($primaryStable, $secondaryStable);
        }

        if ($primaryStable->currentRetirement()->exists()) {
            throw CannotBeMergedException::primaryRetired($primaryStable);
        }

        if ($secondaryStable->currentRetirement()->exists()) {
            throw CannotBeMergedException::secondaryRetired($secondaryStable);
        }

        if (! $primaryStable->currentActivityPeriod()->exists()) {
            throw CannotBeMergedException::primaryNotActive($primaryStable);
        }

        if (! $secondaryStable->currentActivityPeriod()->exists()) {
            throw CannotBeMergedException::secondaryNotActive($secondaryStable);
        }
    }

    public function ensureSplitMembersAvailable(StableMembershipData $members): void
    {
        $unavailableMemberNames = $this->unavailableMemberNames($members);

        if ($unavailableMemberNames !== []) {
            throw CannotBeSplitException::membersUnavailable($unavailableMemberNames);
        }
    }

    public function ensureMergeMembersAvailable(StableMembershipData $members): void
    {
        $unavailableMemberNames = $this->unavailableMemberNames($members);

        if ($unavailableMemberNames !== []) {
            throw CannotBeMergedException::membersUnavailable($unavailableMemberNames);
        }
    }

    /**
     * Why a member cannot be moved to another stable, or null when it is available.
     */
    public function unavailabilityOf(Wrestler|TagTeam $member): ?StableMemberUnavailability
    {
        return match (true) {
            $member->currentRetirement()->exists() => StableMemberUnavailability::Retired,
            ! $member->currentEmployment()->exists() => StableMemberUnavailability::Unemployed,
            $member->currentSuspension()->exists() => StableMemberUnavailability::Suspended,
            $member instanceof Wrestler && $member->currentInjury()->exists() => StableMemberUnavailability::Injured,
            default => null,
        };
    }

    /** @return array<int, string> */
    private function unavailableMemberNames(StableMembershipData $members): array
    {
        return collect([...($members->wrestlers ?? []), ...($members->tagTeams ?? [])])
            ->filter(fn (Wrestler|TagTeam $member): bool => $this->unavailabilityOf($member) instanceof StableMemberUnavailability)
            ->map(fn (Wrestler|TagTeam $member): string => $member->name)
            ->values()
            ->all();
    }
}
