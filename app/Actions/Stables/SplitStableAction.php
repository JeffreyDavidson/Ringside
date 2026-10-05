<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableData;
use App\Data\Stables\StableMembershipData;
use App\Exceptions\Roster\Stables\CannotBeSplitException;
use App\Lifecycle\Roster\Stables\StableMembershipRequirements;
use App\Lifecycle\Roster\Stables\StableNameLock;
use App\Lifecycle\Roster\Stables\StableRestructuringEligibility;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Roster\Stables\StableMembershipService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SplitStableAction
{
    /**
     * Create a new split stable action instance.
     */
    public function __construct(
        protected CreateAction $createAction,
        protected StableMembershipService $membershipService,
        protected RemoveStableMembersAction $removeStableMembersAction,
        protected StableRestructuringEligibility $eligibility,
        protected StableNameLock $nameLock,
    ) {}

    /**
     * Split a stable into two based on member selection.
     *
     * Creates a new stable and transfers specified members from the original
     * stable to the new stable, leaving the remaining members in the original.
     * The new stable belongs to the original stable's promotion, and its name
     * must not be used by another active stable of that promotion. A stable
     * without a promotion has no database-level name guard on MySQL, so its
     * split first takes the name lock, before the original stable's row lock.
     *
     * @param  Stable  $originalStable  The stable to split
     * @param  string  $newStableName  Name for the new stable
     * @param  StableMembershipData  $membersForNewStable  Members to move to new stable
     * @param  Carbon  $date  The date when the split operation occurs
     * @return Stable The newly created stable
     *
     * @throws CannotBeSplitException When the stable, selected members or new name cannot be used for the split
     */
    public function handle(
        Stable $originalStable,
        string $newStableName,
        StableMembershipData $membersForNewStable,
        Carbon $date
    ): Stable {
        return DB::transaction(function () use ($originalStable, $newStableName, $membersForNewStable, $date): Stable {
            if ($originalStable->promotion_id === null) {
                $this->nameLock->lock($newStableName);
            }

            $lockedStable = $originalStable->refreshForUpdate();

            $this->eligibility->ensureCanSplit($lockedStable);

            $this->validateSplitMembers($lockedStable, $membersForNewStable);

            $stableData = new StableData(
                name: mb_trim($newStableName),
                start_date: $date,
                members: $membersForNewStable
            );

            $this->eligibility->ensureSplitNameAvailable($lockedStable, $stableData->getTrimmedName());

            $this->removeStableMembersAction->handle($lockedStable, $membersForNewStable, $date);

            return $this->createAction->handle($stableData, $lockedStable->promotion_id);
        });
    }

    /**
     * Validate that split members are feasible.
     *
     * @param  Stable  $originalStable  The stable being split
     * @param  StableMembershipData  $membersForNewStable  The members being moved
     *
     * @throws CannotBeSplitException When split is not feasible
     */
    private function validateSplitMembers(Stable $originalStable, StableMembershipData $membersForNewStable): void
    {
        if ($membersForNewStable->isEmpty()) {
            throw CannotBeSplitException::noMembersToMove();
        }

        $currentMembers = $this->membershipService->currentMembers($originalStable);
        $nonMemberWrestlerNames = $membersForNewStable->wrestlers
            ?->diff($currentMembers->wrestlers ?? [])
            ->map(fn (Wrestler $wrestler): string => $wrestler->name)
            ->all() ?? [];
        $nonMemberTagTeamNames = $membersForNewStable->tagTeams
            ?->diff($currentMembers->tagTeams ?? [])
            ->map(fn (TagTeam $tagTeam): string => $tagTeam->name)
            ->all() ?? [];
        $nonMemberNames = [...$nonMemberWrestlerNames, ...$nonMemberTagTeamNames];

        if ($nonMemberNames !== []) {
            throw CannotBeSplitException::membersDoNotBelongToStable($nonMemberNames);
        }

        $this->eligibility->ensureSplitMembersAvailable($membersForNewStable);

        $newStableMemberCount = $membersForNewStable->getTotalMemberCount();
        $remainingMemberCount = $currentMembers->getTotalMemberCount() - $newStableMemberCount;

        if ($remainingMemberCount === 0) {
            throw CannotBeSplitException::allMembersMoving();
        }

        if (! StableMembershipRequirements::hasMinimumHeadcount($newStableMemberCount)) {
            throw CannotBeSplitException::resultingStableBelowMinimum('new', $newStableMemberCount, StableMembershipRequirements::MINIMUM_MEMBER_COUNT);
        }

        if (! StableMembershipRequirements::hasMinimumHeadcount($remainingMemberCount)) {
            throw CannotBeSplitException::resultingStableBelowMinimum('original', $remainingMemberCount, StableMembershipRequirements::MINIMUM_MEMBER_COUNT);
        }
    }
}
