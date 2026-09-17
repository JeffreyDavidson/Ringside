<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\Lifecycle\RecordLifecycleTransitionAction;
use App\Actions\Lifecycle\StartActivityPeriodAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Stables\StableActivityTransition;
use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Lifecycle\Roster\Stables\StableActivityEligibility;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Roster\Stables\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EstablishAction
{
    public function __construct(
        protected StartActivityPeriodAction $startActivityPeriodAction,
        protected RecordLifecycleTransitionAction $recordLifecycleTransitionAction,
        protected StableActivityEligibility $eligibility,
    ) {}

    /**
     * Establish a stable and make it active.
     */
    public function handle(
        Stable $stable,
        ?Carbon $activationDate = null,
        ?Carbon $endDate = null,
    ): ActivityPeriod {
        $effectiveActivationDate = $activationDate ?? now();

        if ($endDate?->lt($effectiveActivationDate)) {
            throw InvalidDateRangeException::endBeforeStart($effectiveActivationDate, $endDate, 'stable establishment');
        }

        return DB::transaction(function () use ($stable, $effectiveActivationDate, $endDate): ActivityPeriod {
            $lockedStable = $stable->refreshForUpdate();

            $this->eligibility->ensureAllowed($lockedStable, StableActivityTransition::Establish);

            $activityPeriod = $this->startActivityPeriodAction->handle($lockedStable, $effectiveActivationDate);

            if ($endDate instanceof Carbon) {
                $activityPeriod->update(['ended_at' => $endDate]);
            }

            $this->recordLifecycleTransitionAction->handle(
                $lockedStable,
                LifecycleDimension::Activity,
                LifecycleTransitionType::Established,
                $effectiveActivationDate,
                array_filter(['ended_at' => $endDate?->toDateTimeString()]),
            );

            return $activityPeriod;
        });
    }
}
