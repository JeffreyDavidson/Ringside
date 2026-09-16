<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\Lifecycle\RecordLifecycleTransitionAction;
use App\Actions\Lifecycle\StartActivityPeriodAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Stables\StableActivityTransition;
use App\Lifecycle\Roster\Stables\StableActivityEligibility;
use App\Models\Roster\Stables\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReuniteAction
{
    /**
     * Create a new reunite action instance.
     */
    public function __construct(
        protected StartActivityPeriodAction $startActivityPeriodAction,
        protected RecordLifecycleTransitionAction $recordLifecycleTransitionAction,
        protected StableActivityEligibility $eligibility,
    ) {}

    /**
     * Reunite an inactive stable and make it active again.
     */
    public function handle(Stable $stable, ?Carbon $reuniteDate = null): void
    {
        $effectiveDate = $reuniteDate ?? now();

        DB::transaction(function () use ($stable, $effectiveDate): void {
            $lockedStable = $stable->refreshForUpdate();

            $this->eligibility->ensureAllowed($lockedStable, StableActivityTransition::Reunite);
            $this->startActivityPeriodAction->handle($lockedStable, $effectiveDate);
            $this->recordLifecycleTransitionAction->handle(
                $lockedStable,
                LifecycleDimension::Activity,
                LifecycleTransitionType::Reunited,
                $effectiveDate,
            );
        });
    }
}
