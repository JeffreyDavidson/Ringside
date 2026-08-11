<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\InjuryPeriodManager;
use App\Lifecycle\RetirementPeriodManager;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Referees\Referee;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RetireAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly InjuryPeriodManager $injuryPeriods,
        private readonly RetirementPeriodManager $retirementPeriods,
        private readonly SuspensionPeriodManager $suspensionPeriods,
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
        $referee->ensureCanBeRetired();

        $retirementDate = DateHelper::resolveDate($retirementDate);

        DB::transaction(function () use ($referee, $retirementDate): void {
            if ($referee->isEmployed()) {
                if ($referee->isSuspended()) {
                    $this->suspensionPeriods->end($referee, $retirementDate);
                } elseif ($referee->isInjured()) {
                    $this->injuryPeriods->end($referee, $retirementDate);
                }

                $this->employmentPeriods->end($referee, $retirementDate);
            }

            $this->retirementPeriods->start($referee, $retirementDate);
        });
    }
}
