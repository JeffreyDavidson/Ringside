<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Lifecycle\IndividualRetirementEligibility;
use App\Models\Roster\Managers\Manager;
use App\Services\IndividualRetirementPeriodService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RetireAction
{
    public function __construct(
        private readonly IndividualRetirementPeriodService $retirementPeriods,
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
        $retirementDate = $retirementDate ?? now();

        DB::transaction(function () use ($manager, $retirementDate): void {
            $lockedManager = Manager::query()->whereKey($manager->getKey())->lockForUpdate()->firstOrFail();
            $this->eligibility->ensureCanRetire($lockedManager);

            $this->retirementPeriods->start($lockedManager, $retirementDate);
            $this->endCurrentRelationships->handle($lockedManager, $retirementDate);
        });
    }
}
