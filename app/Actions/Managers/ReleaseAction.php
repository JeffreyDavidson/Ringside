<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\Roster\CannotBeReleasedException;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\InjuryPeriodManager;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Managers\Manager;
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
        $manager->ensureCanBeReleased();

        $releaseDate = DateHelper::resolveDate($releaseDate);

        DB::transaction(function () use ($manager, $releaseDate): void {
            $this->employmentPeriods->end($manager, $releaseDate);

            if ($manager->isSuspended()) {
                $this->suspensionPeriods->end($manager, $releaseDate);
            } elseif ($manager->isInjured()) {
                $this->injuryPeriods->end($manager, $releaseDate);
            }

            $this->endCurrentRelationships->handle($manager, $releaseDate);
        });
    }
}
