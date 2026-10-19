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
     * - Starts a new employment period from the unretirement date only if the manager was employed when they retired
     * - Preserves all historical retirement and employment records
     *
     * @param  Manager  $manager  The manager to unretire
     * @param  Carbon|null  $unretiredDate  The unretirement date (defaults to now)
     * @param  bool|null  $employImmediately  Force employing (true) or not employing (false); null restores the state before retirement
     *
     * @throws CannotBeUnretiredException When manager cannot be unretired due to business rules
     */
    public function handle(Manager $manager, ?Carbon $unretiredDate = null, ?bool $employImmediately = null): void
    {
        $effectiveDate = $unretiredDate ?? now();

        DB::transaction(function () use ($manager, $effectiveDate, $employImmediately): void {
            $lockedManager = $manager->refreshForUpdate();

            $this->eligibility->ensureCanUnretire($lockedManager);
            $employ = $employImmediately ?? $this->retirementPeriods->wasEmployedWhenRetired($lockedManager);
            $this->retirementPeriods->end($lockedManager, $effectiveDate, LifecycleTransitionType::Unretired);

            if ($employ) {
                $this->employmentPeriods->start($lockedManager, $effectiveDate, LifecycleTransitionType::Employed);
            }
        });
    }
}
