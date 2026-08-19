<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\IndividualRetirementEligibility;
use App\Lifecycle\InjuryPeriodManager;
use App\Lifecycle\RetirementPeriodManager;
use App\Lifecycle\SuspensionPeriodManager;
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
        $retirementDate = $retirementDate ?? now();

        DB::transaction(function () use ($referee, $retirementDate): void {
            $lockedReferee = Referee::query()->whereKey($referee->getKey())->lockForUpdate()->firstOrFail();
            $this->eligibility->ensureCanRetire($lockedReferee);

            if ($lockedReferee->isEmployed()) {
                if ($lockedReferee->isSuspended()) {
                    $this->suspensionPeriods->end($lockedReferee, $retirementDate);
                } elseif ($lockedReferee->isInjured()) {
                    $this->injuryPeriods->end($lockedReferee, $retirementDate);
                }

                $this->employmentPeriods->end($lockedReferee, $retirementDate);
            }

            $this->retirementPeriods->start($lockedReferee, $retirementDate, LifecycleTransitionType::Retired);
        });
    }
}
