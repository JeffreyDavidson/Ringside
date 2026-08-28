<?php

declare(strict_types=1);

namespace App\Services\Roster\Stables;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Stables\StableActivityTransition;
use App\Lifecycle\Roster\Stables\StableActivityEligibility;
use App\Models\Roster\Stables\Stable;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class StableDisbandService
{
    public function __construct(
        private readonly StableActivityEligibility $eligibility,
        private readonly StableActivityPeriodService $activityPeriods,
    ) {}

    /**
     * @param  Closure(Stable, Carbon): void|null  $afterDisbandment
     */
    public function disband(
        Stable $stable,
        Carbon $disbandDate,
        ?Closure $afterDisbandment = null,
    ): void {
        DB::transaction(function () use ($stable, $disbandDate, $afterDisbandment): void {
            $lockedStable = $stable->refreshForUpdate();

            $this->eligibility->ensureAllowed($lockedStable, StableActivityTransition::Disband);
            $this->activityPeriods->end($lockedStable, $disbandDate, LifecycleTransitionType::Disbanded);
            $afterDisbandment?->__invoke($lockedStable, $disbandDate);
        });
    }
}
