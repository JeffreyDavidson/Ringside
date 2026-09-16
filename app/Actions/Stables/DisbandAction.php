<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\Lifecycle\EndActivityPeriodAction;
use App\Actions\Lifecycle\RecordLifecycleTransitionAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Stables\StableActivityTransition;
use App\Lifecycle\Roster\Stables\StableActivityEligibility;
use App\Models\Roster\Stables\Stable;
use App\Services\Roster\Stables\StableMembershipService;
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
        protected EndActivityPeriodAction $endActivityPeriodAction,
        protected RecordLifecycleTransitionAction $recordLifecycleTransitionAction,
        protected StableMembershipService $membershipService,
    ) {}

    /**
     * Disband a stable and remove its current members.
     */
    public function handle(Stable $stable, ?Carbon $disbandDate = null): void
    {
        $effectiveDate = $disbandDate ?? now();

        DB::transaction(function () use ($stable, $effectiveDate): void {
            $lockedStable = $stable->refreshForUpdate();

            $this->eligibility->ensureAllowed($lockedStable, StableActivityTransition::Disband);
            $this->endActivityPeriodAction->handle($lockedStable, $effectiveDate);
            $this->recordLifecycleTransitionAction->handle(
                $lockedStable,
                LifecycleDimension::Activity,
                LifecycleTransitionType::Disbanded,
                $effectiveDate,
            );

            $currentMembers = $this->membershipService->currentMembers($lockedStable);

            if ($currentMembers->isNotEmpty()) {
                $this->removeStableMembersAction->handle(
                    $lockedStable,
                    $currentMembers,
                    $effectiveDate,
                );
            }
        });
    }
}
