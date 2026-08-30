<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Stables;

use App\Data\Stables\StableMembershipData;
use App\Exceptions\Roster\Stables\CannotBeMergedException;
use App\Exceptions\Roster\Stables\CannotBeSplitException;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Roster\Stables\StableMembershipService;

final class StableRestructuringEligibility
{
    public function __construct(private readonly StableMembershipService $membershipService) {}

    public function canSplit(Stable $stable): bool
    {
        try {
            $this->ensureCanSplit($stable);

            return true;
        } catch (CannotBeSplitException) {
            return false;
        }
    }

    public function ensureCanSplit(Stable $stable): void
    {
        if ($stable->isRetired()) {
            throw CannotBeSplitException::retired($stable);
        }

        if (! $stable->currentActivityPeriod()->exists()) {
            throw CannotBeSplitException::notActive($stable);
        }

        $minimumMemberCount = StableMembershipData::MINIMUM_MEMBER_COUNT * 2;
        $currentMemberCount = $this->membershipService->currentMembers($stable)->getTotalMemberCount();

        if ($currentMemberCount < $minimumMemberCount) {
            throw CannotBeSplitException::insufficientMembers($stable, $currentMemberCount, $minimumMemberCount);
        }
    }

    public function canMerge(Stable $primaryStable, Stable $secondaryStable): bool
    {
        try {
            $this->ensureCanMerge($primaryStable, $secondaryStable);

            return true;
        } catch (CannotBeMergedException) {
            return false;
        }
    }

    public function ensureCanMerge(Stable $primaryStable, Stable $secondaryStable): void
    {
        if ($primaryStable->is($secondaryStable)) {
            throw CannotBeMergedException::selfMerge($primaryStable);
        }

        if ($primaryStable->isRetired()) {
            throw CannotBeMergedException::primaryRetired($primaryStable);
        }

        if ($secondaryStable->isRetired()) {
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

    /** @return array<int, string> */
    private function unavailableMemberNames(StableMembershipData $members): array
    {
        $unavailableWrestlers = $members->wrestlers?->filter(
            fn (Wrestler $wrestler): bool => ! $wrestler->currentEmployment()->exists()
                || $wrestler->isSuspended()
                || $wrestler->isInjured()
                || $wrestler->isRetired(),
        )->map(fn (Wrestler $wrestler): string => $wrestler->name)->all() ?? [];

        $unavailableTagTeams = $members->tagTeams?->filter(
            fn (TagTeam $tagTeam): bool => ! $tagTeam->currentEmployment()->exists()
                || $tagTeam->isSuspended()
                || $tagTeam->isRetired(),
        )->map(fn (TagTeam $tagTeam): string => $tagTeam->name)->all() ?? [];

        return [...$unavailableWrestlers, ...$unavailableTagTeams];
    }
}
