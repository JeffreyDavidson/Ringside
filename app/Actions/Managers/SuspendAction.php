<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\Roster\CannotBeSuspendedException;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Managers\Manager;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;

class SuspendAction
{
    public function __construct(private readonly SuspensionPeriodManager $suspensionPeriods) {}

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
        $manager->ensureCanBeSuspended();

        $suspensionDate = DateHelper::resolveDate($suspensionDate);

        $this->suspensionPeriods->start($manager, $suspensionDate);
    }
}
