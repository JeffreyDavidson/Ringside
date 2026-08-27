<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Stables\StableActivityTransition;
use App\Exceptions\Roster\Stables\CannotBeDisbandedException;
use App\Lifecycle\StableActivityEligibility;
use App\Models\Roster\Stables\Stable;
use App\Services\StableActivityPeriodService;
use App\Services\StableMembershipService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DisbandAction
{
    /**
     * Create a new disband action instance.
     */
    public function __construct(
        protected RemoveStableMembersAction $removeStableMembersAction,
        protected StableActivityEligibility $eligibility,
        protected StableMembershipService $membershipService,
        protected StableActivityPeriodService $activityPeriods,
    ) {}

    /**
     * Disband a stable.
     *
     * This handles the complete stable disbandment workflow:
     * - Validates the stable can be disbanded (currently active)
     * - Ends the stable's activity period to mark it as disbanded
     * - Removes all current members from the stable
     * - Preserves historical membership records
     * - Members remain available for other opportunities
     *
     * @param  Stable  $stable  The stable to disband
     * @param  Carbon|null  $disbandDate  The disbandment date (defaults to now)
     * @throws CannotBeDisbandedException If the stable cannot be disbanded
     */
    public function handle(Stable $stable, ?Carbon $disbandDate = null): void
    {
        $disbandDate = $disbandDate ?? now();

        DB::transaction(function () use ($stable, $disbandDate): void {
            $lockedStable = Stable::query()
                ->withTrashed()
                ->whereKey($stable->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureAllowed($lockedStable, StableActivityTransition::Disband);
            $this->activityPeriods->end($lockedStable, $disbandDate, LifecycleTransitionType::Disbanded);

            $currentMembers = $this->membershipService->currentMembers($lockedStable);

            if ($currentMembers->isNotEmpty()) {
                $this->removeStableMembersAction->handle(
                    $lockedStable,
                    $currentMembers,
                    $disbandDate,
                );
            }

        });
    }
}
