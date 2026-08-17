<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeReleasedException;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\IndividualEmploymentEligibility;
use App\Lifecycle\InjuryPeriodManager;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Roster\Managers\Manager;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReleaseAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly InjuryPeriodManager $injuryPeriods,
        private readonly SuspensionPeriodManager $suspensionPeriods,
        private readonly EndCurrentRelationshipsAction $endCurrentRelationships,
        private readonly IndividualEmploymentEligibility $eligibility,
    ) {}

    /**
     * Release a manager from employment and end all current relationships.
     *
     * This handles the complete manager release workflow with cascading effects:
     * - Validates the manager can be released (currently employed)
     * - Ends current management relationships through a typed domain action
     * - Ends suspension, injury, and employment through lifecycle period managers
     * - Maintains all historical records for tracking purposes
     *
     * @param  Manager  $manager  The manager to release
     * @param  Carbon|null  $releaseDate  The release date (defaults to now)
     * @throws CannotBeReleasedException When manager cannot be released due to business rules
     */
    public function handle(Manager $manager, ?Carbon $releaseDate = null): void
    {
        $releaseDate = DateHelper::resolveDate($releaseDate);

        DB::transaction(function () use ($manager, $releaseDate): void {
            $lockedManager = Manager::query()->whereKey($manager->getKey())->lockForUpdate()->firstOrFail();
            $this->eligibility->ensureCanRelease($lockedManager);

            $this->employmentPeriods->end($lockedManager, $releaseDate, LifecycleTransitionType::Released);

            if ($lockedManager->isSuspended()) {
                $this->suspensionPeriods->end($lockedManager, $releaseDate);
            } elseif ($lockedManager->isInjured()) {
                $this->injuryPeriods->end($lockedManager, $releaseDate);
            }

            $this->endCurrentRelationships->handle($lockedManager, $releaseDate);
        });
    }
}
