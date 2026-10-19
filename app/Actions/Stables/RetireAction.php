<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\Lifecycle\EndActivityPeriodAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Periods\RetirementPeriodManager;
use App\Lifecycle\Roster\Stables\StableRetirementEligibility;
use App\Models\Roster\Stables\Stable;
use App\Services\Roster\Stables\StableMembershipService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RetireAction
{
    /**
     * Create a new retire action instance.
     */
    public function __construct(
        protected EndActivityPeriodAction $endActivityPeriodAction,
        protected RetirementPeriodManager $retirementPeriods,
        protected StableRetirementEligibility $stableRetirementEligibility,
        protected RemoveStableMembersAction $removeStableMembersAction,
        protected StableMembershipService $membershipService,
    ) {}

    /**
     * Retire a stable: end its operations and its memberships. The members are not retired; they keep their careers.
     */
    public function handle(Stable $stable, ?Carbon $retirementDate = null): void
    {
        $retirementDate ??= now();
        $operationalDate = $retirementDate->isFuture() ? now() : $retirementDate;

        DB::transaction(function () use ($stable, $retirementDate, $operationalDate): void {
            $lockedStable = $stable->refreshForUpdate();

            $this->stableRetirementEligibility->ensureCanRetire($lockedStable);

            if ($lockedStable->currentActivityPeriod()->exists()) {
                $this->endActivityPeriodAction->handle($lockedStable, $operationalDate);
            }

            $this->removeStableMembersAction->handle(
                $lockedStable,
                $this->membershipService->currentMembers($lockedStable),
                $operationalDate,
            );

            $this->retirementPeriods->start($lockedStable, $retirementDate, LifecycleTransitionType::Retired);
        });
    }
}
