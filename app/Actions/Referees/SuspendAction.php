<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeSuspendedException;
use App\Lifecycle\IndividualSuspensionEligibility;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Roster\Referees\Referee;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SuspendAction
{
    public function __construct(
        private readonly SuspensionPeriodManager $suspensionPeriods,
        private readonly IndividualSuspensionEligibility $eligibility,
    ) {}

    /**
     * Suspend a referee.
     *
     * This handles the complete referee suspension workflow:
     * - Validates the referee can be suspended (currently employed, not already suspended)
     * - Creates a suspension record with the specified start date
     * - Removes the referee from active match officiating duties
     * - Maintains employment status while restricting availability
     *
     * @param  Referee  $referee  The referee to suspend
     * @param  Carbon|null  $suspensionDate  The suspension start date (defaults to now)
     * @throws CannotBeSuspendedException When referee cannot be suspended due to business rules
     */
    public function handle(Referee $referee, ?Carbon $suspensionDate = null): void
    {
        $suspensionDate = DateHelper::resolveDate($suspensionDate);

        DB::transaction(function () use ($referee, $suspensionDate): void {
            $lockedReferee = Referee::query()->whereKey($referee->getKey())->lockForUpdate()->firstOrFail();
            $this->eligibility->ensureCanSuspend($lockedReferee);

            $this->suspensionPeriods->start($lockedReferee, $suspensionDate, LifecycleTransitionType::Suspended);
        });
    }
}
