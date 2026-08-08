<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Actions\Concerns\StatusTransitionPipeline;
use App\Models\Managers\Manager;
use Exception;
use Illuminate\Support\Carbon;

class EmployAction
{
    /**
     * Employ a manager.
     *
     * This handles the complete manager employment workflow using the StatusTransitionPipeline:
     * - Validates the manager can be employed (not retired, not already employed)
     * - Ends retirement if currently retired
     * - Creates an employment record with the specified start date
     * - Makes the manager available for talent management assignments
     *
     * @param  Manager  $manager  The manager to employ
     * @param  Carbon|null  $startDate  The employment start date (defaults to now)
     * @throws Exception When manager cannot be employed due to business rules
     */
    public function handle(Manager $manager, ?Carbon $startDate = null): void
    {
        $manager->ensureCanBeEmployed();

        StatusTransitionPipeline::employ($manager, $startDate)->execute();
    }
}
