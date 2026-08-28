<?php

declare(strict_types=1);

namespace App\Services\Roster\Stables;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Stables\StableActivityTransition;
use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Lifecycle\StableActivityEligibility;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Roster\Stables\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class StableEstablishmentService
{
    public function __construct(
        private readonly StableActivityEligibility $eligibility,
        private readonly StableActivityPeriodService $activityPeriods,
    ) {}

    public function establish(Stable $stable, Carbon $activationDate, ?Carbon $endDate = null): ActivityPeriod
    {
        if ($endDate?->lt($activationDate)) {
            throw InvalidDateRangeException::endBeforeStart($activationDate, $endDate, 'stable establishment');
        }

        return DB::transaction(function () use ($stable, $activationDate, $endDate): ActivityPeriod {
            $lockedStable = $stable->refreshForUpdate();

            $this->eligibility->ensureAllowed($lockedStable, StableActivityTransition::Establish);

            return $this->activityPeriods->start($lockedStable, $activationDate, LifecycleTransitionType::Established, $endDate);
        });
    }
}
