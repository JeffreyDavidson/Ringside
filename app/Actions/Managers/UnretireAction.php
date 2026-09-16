<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeUnretiredException;
use App\Lifecycle\Periods\EmploymentPeriodManager;
use App\Lifecycle\Periods\RetirementPeriodManager;
use App\Lifecycle\Roster\Individuals\IndividualRetirementEligibility;
use App\Models\Roster\Managers\Manager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UnretireAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly RetirementPeriodManager $retirementPeriods,
        private readonly IndividualRetirementEligibility $eligibility,
    ) {}

    /**
     * Unretire a retired manager and return them to active talent management.
     *
     * This handles the complete manager unretirement workflow:
     * - Validates the manager can be unretired (currently retired)
     * - Ends the current retirement period through RetirementPeriodManager
     * - Optionally starts a new employment period from the unretirement date
     * - Preserves all historical retirement and employment records
     *
     * @param  Manager  $manager  The manager to unretire
     * @param  Carbon|null  $unretiredDate  The unretirement date (defaults to now)
     * @param  bool  $employImmediately  Whether to employ the manager immediately
     * @throws CannotBeUnretiredException When manager cannot be unretired due to business rules
     */
    public function handle(Manager $manager, ?Carbon $unretiredDate = null, bool $employImmediately = true): void
    {
        $effectiveDate = $unretiredDate ?? now();

        DB::transaction(function () use ($manager, $effectiveDate, $employImmediately): void {
            $lockedManager = $manager->refreshForUpdate();

            $this->eligibility->ensureCanUnretire($lockedManager);
            $this->retirementPeriods->end($lockedManager, $effectiveDate, LifecycleTransitionType::Unretired);

            if ($employImmediately) {
                $this->employmentPeriods->start($lockedManager, $effectiveDate, LifecycleTransitionType::Employed);
            }
        });
    }
}
