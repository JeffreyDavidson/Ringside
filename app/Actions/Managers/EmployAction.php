<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeEmployedException;
use App\Lifecycle\Periods\EmploymentPeriodManager;
use App\Lifecycle\Roster\Individuals\IndividualEmploymentEligibility;
use App\Models\Roster\Managers\Manager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmployAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly IndividualEmploymentEligibility $eligibility,
    ) {}

    /**
     * Employ a manager.
     *
     * This handles the complete manager employment workflow:
     * - Validates the manager can be employed (not retired, not already employed)
     * - Creates the employment record through the shared lifecycle component
     * - Makes the manager available for talent management assignments
     *
     * @param  Manager  $manager  The manager to employ
     * @param  Carbon|null  $startDate  The employment start date (defaults to now)
     * @throws CannotBeEmployedException When the manager cannot be employed
     */
    public function handle(Manager $manager, ?Carbon $startDate = null): void
    {
        $effectiveDate = $startDate ?? now();

        DB::transaction(function () use ($manager, $effectiveDate): void {
            $lockedManager = $manager->refreshForUpdate();

            $this->eligibility->ensureCanEmploy($lockedManager);
            $this->employmentPeriods->start($lockedManager, $effectiveDate, LifecycleTransitionType::Employed);
        });
    }
}
