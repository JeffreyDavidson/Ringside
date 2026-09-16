<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Lifecycle\Roster\Individuals\IndividualRetirementEligibility;
use App\Models\Roster\Managers\Manager;
use App\Services\Roster\Individuals\IndividualRetirementPeriodService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RetireAction
{
    public function __construct(
        private readonly IndividualRetirementPeriodService $retirementPeriods,
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
            $this->retirementPeriods->start($lockedManager, $effectiveDate);
            $this->endCurrentRelationships->handle($lockedManager, $effectiveDate);
        });
    }
}
