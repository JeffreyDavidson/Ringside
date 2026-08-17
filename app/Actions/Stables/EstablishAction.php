<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\Lifecycle\RecordLifecycleTransitionAction;
use App\Actions\Lifecycle\StartActivityPeriodAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Stables\StableActivityTransition;
use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Exceptions\Roster\Stables\CannotBeEstablishedException;
use App\Lifecycle\StableActivityEligibility;
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
     *
     * This handles the complete stable establishment workflow:
     * - Validates the stable can be established (currently unactivated)
     * - Creates an establishment record with the specified date
     * - Makes the stable available for storylines and championship opportunities
     * - Activates the stable's debut period
     *
     * @param  Stable  $stable  The stable to establish
     * @param  Carbon|null  $activationDate  The establishment date (defaults to now)
     * @param  Carbon|null  $endDate  The date the stable stopped being active
     * @throws CannotBeEstablishedException When stable cannot be established due to business rules
     * @throws InvalidDateRangeException When the end date precedes the activation date
     */
    public function handle(
        Stable $stable,
        ?Carbon $activationDate = null,
        ?Carbon $endDate = null,
    ): ActivityPeriod {
        $activationDate = $activationDate ?? now();

        if ($endDate?->lt($activationDate)) {
            throw InvalidDateRangeException::endBeforeStart($activationDate, $endDate, 'stable establishment');
        }

        return DB::transaction(
            function () use ($stable, $activationDate, $endDate): ActivityPeriod {
                $lockedStable = Stable::query()
                    ->withTrashed()
                    ->whereKey($stable->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->eligibility->ensureAllowed($lockedStable, StableActivityTransition::Establish);

                $activityPeriod = $this->startActivityPeriodAction->handle($lockedStable, $activationDate);

                if ($endDate) {
                    $activityPeriod->update(['ended_at' => $endDate]);
                }

                $this->recordLifecycleTransitionAction->handle(
                    $lockedStable,
                    LifecycleDimension::Activity,
                    LifecycleTransitionType::Established,
                    $activationDate,
                    array_filter(['ended_at' => $endDate?->toDateTimeString()]),
                );

                return $activityPeriod;
            }
        );
    }
}
