<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\Lifecycle\EndActivityPeriodAction;
use App\Data\Stables\StableMembershipData;
use App\Lifecycle\StableRestructuringEligibility;
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
        protected StableMembershipService $membershipService,
        protected EndActivityPeriodAction $endActivityPeriodAction,
        protected StableRestructuringEligibility $eligibility,
    ) {}

    /**
     * Merge two stables into one.
     *
     * Transfers all members from the secondary stable to the primary stable
     * and optionally deletes the secondary stable if the operation is successful.
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

            $members = new StableMembershipData(
                wrestlers: $lockedSecondaryStable->currentWrestlers,
                tagTeams: $lockedSecondaryStable->currentTagTeams,
            );

            $this->eligibility->ensureMergeMembersAvailable($members);

            $this->membershipService->removeMembers($lockedSecondaryStable, $members, $date);
            $this->membershipService->addMembers($lockedPrimaryStable, $members, $date);
            $this->endActivityPeriodAction->handle($lockedSecondaryStable, $date);
            $lockedSecondaryStable->delete();
        });
    }
}
