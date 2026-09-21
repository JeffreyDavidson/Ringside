<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Periods\EmploymentPeriodManager;
use App\Lifecycle\Periods\InjuryPeriodManager;
use App\Lifecycle\Periods\SuspensionPeriodManager;
use App\Lifecycle\Roster\Individuals\IndividualEmploymentEligibility;
use App\Models\Roster\Managers\Manager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReleaseAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly InjuryPeriodManager $injuryPeriods,
        private readonly SuspensionPeriodManager $suspensionPeriods,
        private readonly IndividualEmploymentEligibility $eligibility,
        private readonly EndCurrentRelationshipsAction $endCurrentRelationships,
    ) {}

    /**
     * Release a manager from employment and end all current relationships.
     */
    public function handle(Manager $manager, ?Carbon $releaseDate = null): void
    {
        $effectiveDate = $releaseDate ?? now();

        DB::transaction(function () use ($manager, $effectiveDate): void {
            $lockedManager = $manager->refreshForUpdate();

            $this->eligibility->ensureCanRelease($lockedManager);

            $this->employmentPeriods->end($lockedManager, $effectiveDate, LifecycleTransitionType::Released);

            if ($lockedManager->currentSuspension()->exists()) {
                $this->suspensionPeriods->end($lockedManager, $effectiveDate);
            } elseif ($lockedManager->currentInjury()->exists()) {
                $this->injuryPeriods->end($lockedManager, $effectiveDate);
            }

            $this->endCurrentRelationships->handle($lockedManager, $effectiveDate);
        });
    }
}
