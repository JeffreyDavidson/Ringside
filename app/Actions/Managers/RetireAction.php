<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Lifecycle\Periods\EmploymentPeriodManager;
use App\Lifecycle\Periods\InjuryPeriodManager;
use App\Lifecycle\Periods\RetirementPeriodManager;
use App\Lifecycle\Periods\SuspensionPeriodManager;
use App\Lifecycle\Roster\Individuals\IndividualRetirementEligibility;
use App\Models\Roster\Managers\Manager;
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
     * Retire a manager and end their management career.
     *
     * This handles the complete manager retirement workflow with cascading effects:
     * - Validates the manager can be retired (currently employed/active)
     * - Ends current management relationships through a typed domain action
     * - Ends suspension, injury, and employment through lifecycle period managers
     * - Starts a retirement period to formally end their management career
     * - Makes the manager unavailable for future talent management
     * - Preserves all historical records and relationships
     *
     * @param  Manager  $manager  The manager to retire
     * @param  Carbon|null  $retirementDate  The retirement date (defaults to now)
     * @throws CannotBeRetiredException When manager cannot be retired due to business rules
     */
    public function handle(Manager $manager, ?Carbon $retirementDate = null): void
    {
        $effectiveDate = $retirementDate ?? now();

        DB::transaction(function () use ($manager, $effectiveDate): void {
            $lockedManager = $manager->refreshForUpdate();

            $this->eligibility->ensureCanRetire($lockedManager);

            if ($lockedManager->currentEmployment()->exists()) {
                $this->employmentPeriods->end($lockedManager, $effectiveDate);
            }

            if ($lockedManager->currentSuspension()->exists()) {
                $this->suspensionPeriods->end($lockedManager, $effectiveDate);
            } elseif ($lockedManager->currentInjury()->exists()) {
                $this->injuryPeriods->end($lockedManager, $effectiveDate);
            }

            $this->retirementPeriods->start($lockedManager, $effectiveDate, LifecycleTransitionType::Retired);
            $this->endCurrentRelationships->handle($lockedManager, $effectiveDate);
        });
    }
}
