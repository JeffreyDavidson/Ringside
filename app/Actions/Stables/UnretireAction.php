<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\Lifecycle\StartActivityPeriodAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Periods\RetirementPeriodManager;
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
        protected RetirementPeriodManager $retirementPeriods,
        protected StableRetirementEligibility $eligibility,
    ) {}

    /**
     * Unretire a retired stable and optionally make it active again.
     */
    public function handle(
        Stable $stable,
        ?Carbon $unretiredDate = null,
        bool $establishImmediately = true,
        bool $requireFormerMembers = true
    ): void {
        $effectiveDate = $unretiredDate ?? now();

        DB::transaction(function () use ($stable, $effectiveDate, $establishImmediately, $requireFormerMembers): void {
            $lockedStable = $stable->refreshForUpdate();

            $this->eligibility->ensureCanUnretire($lockedStable, $requireFormerMembers);
            $this->retirementPeriods->end($lockedStable, $effectiveDate, LifecycleTransitionType::Unretired);

            if ($establishImmediately) {
                $this->startActivityPeriodAction->handle($lockedStable, $effectiveDate);
            }
        });
    }
}
