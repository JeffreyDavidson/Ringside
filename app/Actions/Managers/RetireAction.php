<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Actions\Concerns\Cascades\ManagerRetirementCascadeStrategy;
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
    ) {}

    /**
     * Retire a manager and end their management career.
     *
     * This handles the complete manager retirement workflow with cascading effects:
     * - Uses StatusTransitionPipeline for consistent retirement handling
     * - Validates the manager can be retired (currently employed/active)
     * - Uses ManagerRetirementCascadeStrategy to end management relationships
     * - Ends suspension, injury, and employment through pipeline
     * - Creates retirement record to formally end their management career
     * - Makes the manager unavailable for future talent management
     * - Preserves all historical records and relationships
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline with cascade strategies for comprehensive
     * retirement handling, following the same pattern as other entity types.
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
            ManagerRetirementCascadeStrategy::comprehensive()($manager, $retirementDate);
        });
    }
}
