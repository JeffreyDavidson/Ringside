<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableData;
use App\Data\Stables\StableMembershipData;
use App\Exceptions\Roster\Stables\CannotBeSplitException;
use App\Models\Stables\Stable;
use App\Services\StableMembershipService;
use App\Services\StableValidationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SplitStableAction
{
    /**
     * Create a new split stable action instance.
     */
    public function __construct(
        protected CreateAction $createAction,
        protected StableMembershipService $membershipService,
        protected StableValidationService $validationService,
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
     * @return Stable The newly created stable
     */
    public function handle(
        Stable $originalStable,
        string $newStableName,
        StableMembershipData $membersForNewStable,
        Carbon $date
    ): Stable {
        return DB::transaction(function () use ($originalStable, $newStableName, $membersForNewStable, $date): Stable {
            // Validate stable can be split using model validation
            $originalStable->ensureCanBeSplit();

            // Validate split member distribution
            $this->validateSplitMembers($originalStable, $membersForNewStable);

            // Validate name uniqueness using service
            $this->validationService->validateUniqueName(mb_trim($newStableName));

            // Use enhanced DTO method to filter employed members
            $employedMembers = $membersForNewStable->filterEmployedMembers();

            // Validate the filtered members are still viable
            if ($employedMembers->isEmpty()) {
                throw new InvalidArgumentException('Cannot split stable: no employed members available for new stable.');
            }

            // Create StableData for the new stable
            $stableData = new StableData(
                name: mb_trim($newStableName),
                start_date: $date,
                members: $employedMembers
            );

            // Use injected CreateAction to create and establish the new stable with members
            $newStable = $this->createAction->handle($stableData);

            // Remove transferred members from original stable using service
            $this->membershipService->removeMembers($originalStable, $employedMembers, $date);

            return $newStable;
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

        $totalMembersBeingSplit = $membersForNewStable->getTotalMemberCount();
        $totalCurrentMembers = $originalStable->currentWrestlers->count() + $originalStable->currentTagTeams->count();
        $remainingMembers = $totalCurrentMembers - $totalMembersBeingSplit;

        if ($remainingMembers === 0) {
            throw CannotBeSplitException::allMembersMoving();
        }
    }
}
