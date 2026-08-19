<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeSuspendedException;
use App\Lifecycle\IndividualSuspensionEligibility;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Roster\Managers\Manager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SuspendAction
{
    public function __construct(
        private readonly SuspensionPeriodManager $suspensionPeriods,
        private readonly IndividualSuspensionEligibility $eligibility,
    ) {}

    /**
     * Suspend a manager.
     *
     * This handles the complete manager suspension workflow:
     * - Validates the manager can be suspended (currently employed, not already suspended)
     * - Creates a suspension record through the shared lifecycle component
     * - Temporarily removes the manager from active wrestler/tag team management duties
     * - Maintains employment status while restricting availability
     *
     * @param  Manager  $manager  The manager to suspend
     * @param  Carbon|null  $suspensionDate  The suspension start date (defaults to now)
     * @throws CannotBeSuspendedException When manager cannot be suspended due to business rules
     */
    public function handle(Manager $manager, ?Carbon $suspensionDate = null): void
    {
        $suspensionDate = $suspensionDate ?? now();

        DB::transaction(function () use ($manager, $suspensionDate): void {
            $lockedManager = Manager::query()->whereKey($manager->getKey())->lockForUpdate()->firstOrFail();
            $this->eligibility->ensureCanSuspend($lockedManager);

            $this->suspensionPeriods->start($lockedManager, $suspensionDate, LifecycleTransitionType::Suspended);
        });
    }
}
