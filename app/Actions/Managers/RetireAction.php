<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\Roster\CannotBeRetiredException;
use App\Lifecycle\EmploymentPeriodManager;
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
        $manager->ensureCanBeRetired();

        $retirementDate = DateHelper::resolveDate($retirementDate);

        DB::transaction(function () use ($manager, $retirementDate): void {
            if ($manager->isEmployed()) {
                $this->employmentPeriods->end($manager, $retirementDate);
            }

            if ($manager->isSuspended()) {
                $this->suspensionPeriods->end($manager, $retirementDate);
            } elseif ($manager->isInjured()) {
                $this->injuryPeriods->end($manager, $retirementDate);
            }

            $this->retirementPeriods->start($manager, $retirementDate);
            $this->endCurrentRelationships->handle($manager, $retirementDate);
        });
    }
}
