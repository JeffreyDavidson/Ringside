<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Lifecycle\Periods\EmploymentPeriodManager;
use App\Lifecycle\Periods\InjuryPeriodManager;
use App\Lifecycle\Periods\RetirementPeriodManager;
use App\Lifecycle\Periods\SuspensionPeriodManager;
use App\Lifecycle\Roster\Individuals\IndividualRetirementEligibility;
use App\Models\Roster\Referees\Referee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RetireAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly InjuryPeriodManager $injuryPeriods,
        private readonly RetirementPeriodManager $retirementPeriods,
        private readonly SuspensionPeriodManager $suspensionPeriods,
        private readonly IndividualRetirementEligibility $eligibility,
    ) {}

    /**
     * Retire a referee and end their officiating career.
     *
     * This handles the complete referee retirement workflow:
     * - Validates the referee can be retired (currently employed/active)
     * - Ends suspension and injury if active
     * - Ends employment period if currently employed
     * - Creates retirement record to formally end their officiating career
     * - Makes the referee unavailable for future match assignments
     * - Preserves all historical records and match officiating history
     *
     * @param  Referee  $referee  The referee to retire
     * @param  Carbon|null  $retirementDate  The retirement date (defaults to now)
     * @throws CannotBeRetiredException When referee cannot be retired due to business rules
     */
    public function handle(Referee $referee, ?Carbon $retirementDate = null): void
    {
        $effectiveDate = $retirementDate ?? now();

        DB::transaction(function () use ($referee, $effectiveDate): void {
            $lockedReferee = $referee->refreshForUpdate();

            $this->eligibility->ensureCanRetire($lockedReferee);

            if ($lockedReferee->currentEmployment()->exists()) {
                $this->employmentPeriods->end($lockedReferee, $effectiveDate);
            }

            if ($lockedReferee->currentSuspension()->exists()) {
                $this->suspensionPeriods->end($lockedReferee, $effectiveDate);
            } elseif ($lockedReferee->currentInjury()->exists()) {
                $this->injuryPeriods->end($lockedReferee, $effectiveDate);
            }

            $this->retirementPeriods->start($lockedReferee, $effectiveDate, LifecycleTransitionType::Retired);
        });
    }
}
