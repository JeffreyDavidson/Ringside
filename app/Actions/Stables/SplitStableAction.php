<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableData;
use App\Data\Stables\StableMembershipData;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Stables\CannotBeSplitException;
use App\Lifecycle\Periods\LifecycleTransitionRecorder;
use App\Lifecycle\Roster\Stables\StableNameLock;
use App\Lifecycle\Roster\Stables\StableRestructuringEligibility;
use App\Models\Roster\Stables\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SplitStableAction
{
    /**
     * Create a new split stable action instance.
     */
    public function __construct(
        protected CreateAction $createAction,
        protected RemoveStableMembersAction $removeStableMembersAction,
        protected StableRestructuringEligibility $eligibility,
        protected StableNameLock $nameLock,
        protected LifecycleTransitionRecorder $transitionRecorder,
    ) {}

    /**
     * Split a stable into two based on member selection.
     *
     * Creates a new stable and transfers specified members from the original
     * stable to the new stable, leaving the remaining members in the original.
     * The new stable belongs to the original stable's promotion, and its name
     * must not be used by another active stable of that promotion. A stable
     * without a promotion has no database-level name guard on MySQL, so its
     * split first takes the name lock, before the original stable's row lock. Both stables get a Split
     * transition, so the original's history shows what it spun off and the new stable's shows its origin.
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
        Carbon $date,
    ): Stable {
        return DB::transaction(function () use ($originalStable, $newStableName, $membersForNewStable, $date): Stable {
            if ($originalStable->promotion_id === null) {
                $this->nameLock->lock($newStableName);
            }

            $lockedStable = $originalStable->refreshForUpdate();

            $this->eligibility->ensureCanSplit($lockedStable);

            $this->eligibility->ensureSplitMembersValid($lockedStable, $membersForNewStable);

            $stableData = new StableData(
                name: mb_trim($newStableName),
                start_date: $date,
                members: $membersForNewStable,
            );

            $this->eligibility->ensureSplitNameAvailable($lockedStable, mb_trim($stableData->name));

            $this->removeStableMembersAction->handle($lockedStable, $membersForNewStable, $date);

            $newStable = $this->createAction->handle($stableData, $lockedStable->promotion_id);

            $this->transitionRecorder->record(
                $lockedStable,
                LifecycleDimension::Activity,
                LifecycleTransitionType::Split,
                $date,
                ['new_stable_id' => $newStable->getKey(), 'new_stable_name' => $newStable->name],
            );
            $this->transitionRecorder->record(
                $newStable,
                LifecycleDimension::Activity,
                LifecycleTransitionType::Split,
                $date,
                ['split_from_stable_id' => $lockedStable->getKey(), 'split_from_stable_name' => $lockedStable->name],
            );

            return $newStable;
        });
    }
}
