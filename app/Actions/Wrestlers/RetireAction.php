<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Lifecycle\Periods\EmploymentPeriodManager;
use App\Lifecycle\Periods\InjuryPeriodManager;
use App\Lifecycle\Periods\RetirementPeriodManager;
use App\Lifecycle\Periods\SuspensionPeriodManager;
use App\Lifecycle\Roster\Individuals\IndividualRetirementEligibility;
use App\Models\Roster\Wrestlers\Wrestler;
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
        private readonly EndCurrentRelationshipsAction $endCurrentRelationships,
    ) {}

    /**
     * Retire a wrestler and end their career.
     *
     * This handles the complete wrestler retirement workflow:
     * - Validates the wrestler can be retired
     * - Ends employment, suspension, and injury through lifecycle period managers
     * - Ends all current professional relationships through a typed domain action
     * - Starts a retirement period
     * - Makes the wrestler permanently unavailable for competition
     * - Preserves the operation's transaction boundary
     *
     * @param  Wrestler  $wrestler  The wrestler to retire
     * @param  Carbon|null  $retirementDate  The retirement start date (defaults to now)
     * @throws CannotBeRetiredException When wrestler cannot be retired due to business rules
     */
    public function handle(Wrestler $wrestler, ?Carbon $retirementDate = null): void
    {
        $effectiveDate = $retirementDate ?? now();

        DB::transaction(function () use ($wrestler, $effectiveDate): void {
            $lockedWrestler = $wrestler->refreshForUpdate();

            $this->eligibility->ensureCanRetire($lockedWrestler);

            if ($lockedWrestler->currentEmployment()->exists()) {
                $this->employmentPeriods->end($lockedWrestler, $effectiveDate);
            }

            if ($lockedWrestler->currentSuspension()->exists()) {
                $this->suspensionPeriods->end($lockedWrestler, $effectiveDate);
            } elseif ($lockedWrestler->currentInjury()->exists()) {
                $this->injuryPeriods->end($lockedWrestler, $effectiveDate);
            }

            $this->retirementPeriods->start($lockedWrestler, $effectiveDate, LifecycleTransitionType::Retired);
            $this->endCurrentRelationships->handle($lockedWrestler, $effectiveDate);
        });
    }
}
