<?php

declare(strict_types=1);

namespace App\Services\Roster\Stables;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Stables\StableActivityTransition;
use App\Lifecycle\StableActivityEligibility;
use App\Models\Roster\Stables\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class StableReuniteService
{
    public function __construct(
        private readonly StableActivityEligibility $eligibility,
        private readonly StableActivityPeriodService $activityPeriods,
    ) {}

    public function reunite(Stable $stable, Carbon $reuniteDate): void
    {
        DB::transaction(function () use ($stable, $reuniteDate): void {
            $lockedStable = $stable->refreshForUpdate();

            $this->eligibility->ensureAllowed($lockedStable, StableActivityTransition::Reunite);
            $this->activityPeriods->start($lockedStable, $reuniteDate, LifecycleTransitionType::Reunited);
        });
    }
}
