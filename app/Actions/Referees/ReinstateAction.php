<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeReinstatedException;
use App\Lifecycle\IndividualSuspensionEligibility;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Roster\Referees\Referee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReinstateAction
{
    public function __construct(
        private readonly SuspensionPeriodManager $suspensionPeriods,
        private readonly IndividualSuspensionEligibility $eligibility,
    ) {}

    /**
     * Reinstate a suspended referee.
     *
     * This handles the complete referee reinstatement workflow:
     * - Validates the referee can be reinstated (currently suspended)
     * - Ends the current suspension period with the specified date
     * - Restores the referee to active officiating status
     * - Makes the referee available for match assignments
     *
     * @param  Referee  $referee  The referee to reinstate
     * @param  Carbon|null  $reinstatementDate  The reinstatement date (defaults to now)
     * @throws CannotBeReinstatedException When referee cannot be reinstated due to business rules
     */
    public function handle(Referee $referee, ?Carbon $reinstatementDate = null): void
    {
        $reinstatementDate = $reinstatementDate ?? now();

        DB::transaction(function () use ($referee, $reinstatementDate): void {
            $lockedReferee = Referee::query()->whereKey($referee->getKey())->lockForUpdate()->firstOrFail();
            $this->eligibility->ensureCanReinstate($lockedReferee);

            $this->suspensionPeriods->end($lockedReferee, $reinstatementDate, LifecycleTransitionType::Reinstated);
        });
    }
}
