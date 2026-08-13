<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\IndividualRetirementEligibility;
use App\Lifecycle\InjuryPeriodManager;
use App\Lifecycle\RetirementPeriodManager;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Managers\Manager;
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
        private readonly EndCurrentRelationshipsAction $endCurrentRelationships,
        private readonly IndividualRetirementEligibility $eligibility,
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
        $retirementDate = DateHelper::resolveDate($retirementDate);

        DB::transaction(function () use ($manager, $retirementDate): void {
            $lockedManager = Manager::query()->lockForUpdate()->findOrFail($manager->getKey());
            $this->eligibility->ensureCanRetire($lockedManager);

            if ($lockedManager->isEmployed()) {
                $this->employmentPeriods->end($lockedManager, $retirementDate);
            }

            if ($lockedManager->isSuspended()) {
                $this->suspensionPeriods->end($lockedManager, $retirementDate);
            } elseif ($lockedManager->isInjured()) {
                $this->injuryPeriods->end($lockedManager, $retirementDate);
            }

            $this->retirementPeriods->start($lockedManager, $retirementDate, LifecycleTransitionType::Retired);
            $this->endCurrentRelationships->handle($lockedManager, $retirementDate);
        });
    }
}
