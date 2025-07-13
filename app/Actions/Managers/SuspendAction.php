<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\Status\CannotBeSuspendedException;
use App\Models\Managers\Manager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SuspendAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Suspend a manager.
     *
     * This handles the complete manager suspension workflow:
     * - Validates the manager can be suspended (currently employed, not already suspended)
     * - Creates a suspension record with the specified start date
     * - Temporarily removes the manager from active wrestler/tag team management duties
     * - Maintains employment status while restricting availability
     *
     * @param  Manager  $manager  The manager to suspend
     * @param  Carbon|null  $suspensionDate  The suspension start date (defaults to now)
     *
     * @throws CannotBeSuspendedException When manager cannot be suspended due to business rules
     *
     * @example
     * ```php
     * // Suspend manager immediately
     * SuspendAction::run($manager);
     *
     * // Schedule suspension for future date
     * SuspendAction::run($manager, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(Manager $manager, ?Carbon $suspensionDate = null): void
    {
        $manager->ensureCanBeSuspended();

        $suspensionDate = $this->getEffectiveDate($suspensionDate);

        DB::transaction(function () use ($manager, $suspensionDate): void {
            $this->managerRepository->createSuspension($manager, $suspensionDate);
        });
    }
}
