<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeClearedFromInjuryException;
use App\Lifecycle\IndividualInjuryEligibility;
use App\Lifecycle\InjuryPeriodManager;
use App\Models\Roster\Managers\Manager;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ClearFromInjuryAction
{
    public function __construct(
        private readonly InjuryPeriodManager $injuryPeriods,
        private readonly IndividualInjuryEligibility $eligibility,
    ) {}

    /**
     * Clear a manager from injury and return them to active management.
     *
     * This handles the complete injury recovery workflow:
     * - Validates the manager can be cleared from injury (currently injured)
     * - Ends the injury period through the shared lifecycle component
     * - Restores the manager to active talent management duties
     * - Makes the manager available for wrestler and tag team assignments again
     * - Preserves injury history for medical and administrative records
     *
     * @param  Manager  $manager  The injured manager to clear
     * @param  Carbon|null  $recoveryDate  The recovery date (defaults to now)
     * @throws CannotBeClearedFromInjuryException When manager cannot be cleared due to business rules
     */
    public function handle(Manager $manager, ?Carbon $recoveryDate = null): void
    {
        $recoveryDate = DateHelper::resolveDate($recoveryDate);

        DB::transaction(function () use ($manager, $recoveryDate): void {
            $lockedManager = Manager::query()->whereKey($manager->getKey())->lockForUpdate()->firstOrFail();
            $this->eligibility->ensureCanBeClearedFromInjury($lockedManager);

            $this->injuryPeriods->end($lockedManager, $recoveryDate, LifecycleTransitionType::ClearedFromInjury);
        });
    }
}
