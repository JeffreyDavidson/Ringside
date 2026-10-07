<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\Lifecycle\EndActivityPeriodAction;
use App\Actions\Lifecycle\RecordLifecycleTransitionAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Roster\Stables\StableRestructuringEligibility;
use App\Models\Roster\Stables\Stable;
use App\Services\Roster\Stables\StableMembershipService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MergeStablesAction
{
    /**
     * Create a new merge stables action instance.
     */
    public function __construct(
        protected RemoveStableMembersAction $removeStableMembersAction,
        protected AddStableMembersAction $addStableMembersAction,
        protected EndActivityPeriodAction $endActivityPeriodAction,
        protected RecordLifecycleTransitionAction $recordLifecycleTransitionAction,
        protected StableMembershipService $membershipService,
        protected StableRestructuringEligibility $eligibility,
    ) {}

    /**
     * Merge two stables into one.
     *
     * Transfers all members from the secondary stable to the primary stable
     * and ends and soft-deletes the secondary stable. Both stables get a Merged transition, so the
     * primary's history shows what it absorbed and the secondary's shows where it went.
     *
     * @param  Stable  $primaryStable  The stable that will receive all members
     * @param  Stable  $secondaryStable  The stable that will be merged into the primary
     * @param  Carbon  $date  The date when the merge operation occurs
     */
    public function handle(
        Stable $primaryStable,
        Stable $secondaryStable,
        Carbon $date
    ): void {
        DB::transaction(function () use ($primaryStable, $secondaryStable, $date): void {
            [$firstStable, $secondStable] = $primaryStable->getKey() < $secondaryStable->getKey()
                ? [$primaryStable, $secondaryStable]
                : [$secondaryStable, $primaryStable];

            $firstLockedStable = $firstStable->refreshForUpdate();
            $secondLockedStable = $secondStable->refreshForUpdate();

            $lockedPrimaryStable = $firstLockedStable->is($primaryStable)
                ? $firstLockedStable
                : $secondLockedStable;
            $lockedSecondaryStable = $firstLockedStable->is($secondaryStable)
                ? $firstLockedStable
                : $secondLockedStable;

            $this->eligibility->ensureCanMerge($lockedPrimaryStable, $lockedSecondaryStable);

            $members = $this->membershipService->currentMembers($lockedSecondaryStable);

            $this->eligibility->ensureMergeMembersAvailable($members);

            $this->removeStableMembersAction->handle($lockedSecondaryStable, $members, $date);
            $this->addStableMembersAction->handle($lockedPrimaryStable, $members, $date);
            $this->endActivityPeriodAction->handle($lockedSecondaryStable, $date);
            $this->recordLifecycleTransitionAction->handle(
                $lockedPrimaryStable,
                LifecycleDimension::Activity,
                LifecycleTransitionType::Merged,
                $date,
                ['merged_stable_id' => $lockedSecondaryStable->getKey(), 'merged_stable_name' => $lockedSecondaryStable->name],
            );
            $this->recordLifecycleTransitionAction->handle(
                $lockedSecondaryStable,
                LifecycleDimension::Activity,
                LifecycleTransitionType::Merged,
                $date,
                ['merged_into_stable_id' => $lockedPrimaryStable->getKey(), 'merged_into_stable_name' => $lockedPrimaryStable->name],
            );
            $lockedSecondaryStable->delete();
        });
    }
}
