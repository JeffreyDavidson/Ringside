<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableData;
use App\Data\Stables\StableMembershipData;
use App\Exceptions\Roster\Stables\CannotBeSplitException;
use App\Lifecycle\StableRestructuringEligibility;
use App\Models\Stables\Stable;
use App\Services\StableMembershipService;
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
        protected StableRestructuringEligibility $eligibility,
    ) {}

    /**
     * Split a stable into two based on member selection.
     *
     * Creates a new stable and transfers specified members from the original
     * stable to the new stable, leaving the remaining members in the original.
     *
     * @param  Stable  $originalStable  The stable to split
     * @param  string  $newStableName  Name for the new stable
     * @param  StableMembershipData  $membersForNewStable  Members to move to new stable
     * @param  Carbon  $date  The date when the split operation occurs
     * @throws CannotBeSplitException When the stable or selected members cannot be split
     * @return Stable The newly created stable
     */
    public function handle(
        Stable $originalStable,
        string $newStableName,
        StableMembershipData $membersForNewStable,
        Carbon $date
    ): Stable {
        return DB::transaction(function () use ($originalStable, $newStableName, $membersForNewStable, $date): Stable {
            $lockedStable = Stable::query()
                ->lockForUpdate()
                ->findOrFail($originalStable->getKey());

            $this->eligibility->ensureCanSplit($lockedStable);

            $this->validateSplitMembers($lockedStable, $membersForNewStable);

            $stableData = new StableData(
                name: mb_trim($newStableName),
                start_date: $date,
                members: $membersForNewStable
            );

            $this->membershipService->removeMembers($lockedStable, $membersForNewStable, $date);

            return $this->createAction->handle($stableData);
        });
    }

    /**
     * Validate that split members are feasible.
     *
     * @param  Stable  $originalStable  The stable being split
     * @param  StableMembershipData  $membersForNewStable  The members being moved
     * @throws CannotBeSplitException When split is not feasible
     */
    private function validateSplitMembers(Stable $originalStable, StableMembershipData $membersForNewStable): void
    {
        if ($membersForNewStable->isEmpty()) {
            throw CannotBeSplitException::noMembersToMove();
        }

        $currentMembers = $this->membershipService->currentMembers($originalStable);
        $nonMemberNames = [
            ...$membersForNewStable->wrestlers?->diff($currentMembers->wrestlers ?? [])->pluck('name')->all() ?? [],
            ...$membersForNewStable->tagTeams?->diff($currentMembers->tagTeams ?? [])->pluck('name')->all() ?? [],
        ];

        if ($nonMemberNames !== []) {
            throw CannotBeSplitException::membersDoNotBelongToStable($nonMemberNames);
        }

        $unavailableMemberNames = $membersForNewStable->getUnavailableMemberNames();

        if ($unavailableMemberNames !== []) {
            throw CannotBeSplitException::membersUnavailable($unavailableMemberNames);
        }

        $newStableMemberCount = $membersForNewStable->getTotalMemberCount();
        $remainingMemberCount = $currentMembers->getTotalMemberCount() - $newStableMemberCount;

        if ($remainingMemberCount === 0) {
            throw CannotBeSplitException::allMembersMoving();
        }

        if ($newStableMemberCount < Stable::MIN_MEMBERS_COUNT) {
            throw CannotBeSplitException::resultingStableBelowMinimum('new', $newStableMemberCount, Stable::MIN_MEMBERS_COUNT);
        }

        if ($remainingMemberCount < Stable::MIN_MEMBERS_COUNT) {
            throw CannotBeSplitException::resultingStableBelowMinimum('original', $remainingMemberCount, Stable::MIN_MEMBERS_COUNT);
        }
    }
}
