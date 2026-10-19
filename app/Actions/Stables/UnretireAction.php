<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\Lifecycle\StartActivityPeriodAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Periods\RetirementPeriodManager;
use App\Lifecycle\Roster\Stables\StableFormerMemberEligibility;
use App\Lifecycle\Roster\Stables\StableRetirementEligibility;
use App\Models\Roster\Stables\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UnretireAction
{
    /**
     * Create a new unretire action instance.
     */
    public function __construct(
        protected StartActivityPeriodAction $startActivityPeriodAction,
        protected AddStableMembersAction $addStableMembersAction,
        protected RetirementPeriodManager $retirementPeriods,
        protected StableRetirementEligibility $eligibility,
        protected StableFormerMemberEligibility $formerMemberEligibility,
    ) {}

    /**
     * Unretire a retired stable and make it active again, with the members it had when it retired that are
     * available to rejoin; the others are skipped.
     */
    public function handle(Stable $stable, ?Carbon $unretiredDate = null): void
    {
        $effectiveDate = $unretiredDate ?? now();

        DB::transaction(function () use ($stable, $effectiveDate): void {
            $lockedStable = $stable->refreshForUpdate();

            $this->eligibility->ensureCanUnretire($lockedStable);

            // Read before the retirement ends: the members are found through the open retirement's date.
            $returningMembers = $this->formerMemberEligibility->availableMembersAtRetirementFor($lockedStable);

            $this->retirementPeriods->end($lockedStable, $effectiveDate, LifecycleTransitionType::Unretired);
            $this->startActivityPeriodAction->handle($lockedStable, $effectiveDate);
            $this->addStableMembersAction->handle($lockedStable, $returningMembers, $effectiveDate);
        });
    }
}
