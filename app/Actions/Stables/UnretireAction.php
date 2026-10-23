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
     * Unretire a retired stable back to how it was before it retired. One that was active becomes active again, with
     * the members it had when it retired that are available to rejoin (the others are skipped). One that was already
     * disbanded stays disbanded with no members, ready to be reunited.
     */
    public function handle(Stable $stable, ?Carbon $unretiredDate = null): void
    {
        $effectiveDate = $unretiredDate ?? now();

        DB::transaction(function () use ($stable, $effectiveDate): void {
            $lockedStable = $stable->refreshForUpdate();

            $this->eligibility->ensureCanUnretire($lockedStable);

            // Read before the retirement ends: both are found through the open retirement.
            if (! $this->formerMemberEligibility->wasActiveWhenRetired($lockedStable)) {
                $this->retirementPeriods->end($lockedStable, $effectiveDate, LifecycleTransitionType::Unretired);

                return;
            }

            $returningMembers = $this->formerMemberEligibility->availableMembersAtRetirementFor($lockedStable);

            $this->retirementPeriods->end($lockedStable, $effectiveDate, LifecycleTransitionType::Unretired);
            $this->startActivityPeriodAction->handle($lockedStable, $effectiveDate);
            $this->addStableMembersAction->handle($lockedStable, $returningMembers, $effectiveDate);
        });
    }
}
