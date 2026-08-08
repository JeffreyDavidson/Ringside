<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Actions\Concerns\StatusTransitionPipeline;
use App\Exceptions\Roster\CannotBeSuspendedException;
use App\Models\Managers\Manager;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;

class SuspendAction
{
    /**
     * Suspend a manager.
     *
     * This handles the complete manager suspension workflow:
     * - Uses StatusTransitionPipeline for consistent suspension handling
     * - Validates the manager can be suspended (currently employed, not already suspended)
     * - Creates a suspension record with the specified start date
     * - Temporarily removes the manager from active wrestler/tag team management duties
     * - Maintains employment status while restricting availability
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline for consistent status handling, following the same
     * pattern as other manager actions.
     *
     * @param  Manager  $manager  The manager to suspend
     * @param  Carbon|null  $suspensionDate  The suspension start date (defaults to now)
     * @throws CannotBeSuspendedException When manager cannot be suspended due to business rules
     */
    public function handle(Manager $manager, ?Carbon $suspensionDate = null): void
    {
        $manager->ensureCanBeSuspended();

        $suspensionDate = DateHelper::resolveDate($suspensionDate);

        // Use StatusTransitionPipeline for consistent suspension handling
        StatusTransitionPipeline::suspend($manager, $suspensionDate)->execute();
    }
}
