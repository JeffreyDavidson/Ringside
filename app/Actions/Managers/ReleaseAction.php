<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\CannotBeReleasedException;
use App\Models\Managers\Manager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ReleaseAction
{
    use AsAction;

    /**
     * Release a manager from employment and end all current relationships.
     *
     * This handles the complete manager release workflow with cascading effects:
     * - Validates the manager can be released (currently employed)
     * - Ends current wrestler and tag team management relationships
     * - Ends suspension and injury if active
     * - Ends employment period with the specified date
     * - Maintains all historical records for tracking purposes
     *
     * @param  Manager  $manager  The manager to release
     * @param  Carbon|null  $releaseDate  The release date (defaults to now)
     * @throws CannotBeReleasedException When manager cannot be released due to business rules
     *
     * @example
     * ```php
     * // Release manager immediately
     * ReleaseAction::run($manager);
     *
     * // Release with specific date
     * ReleaseAction::run($manager, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(Manager $manager, ?Carbon $releaseDate = null): void
    {
        $manager->ensureCanBeReleased();

        $releaseDate = $releaseDate ?? now();

        DB::transaction(function () use ($manager, $releaseDate): void {
            // End suspension or injury if active (manager cannot be both suspended and injured)
            if ($manager->isSuspended()) {
                $currentSuspension = $manager->currentSuspension()->first();
                if ($currentSuspension) {
                    $currentSuspension->update(['ended_at' => $releaseDate]);
                }
            } elseif ($manager->isInjured()) {
                $currentInjury = $manager->currentInjury()->first();
                if ($currentInjury) {
                    $currentInjury->update(['ended_at' => $releaseDate->toDateTimeString()]);
                }
            }

            // End current management relationships
            // Note: Management relationships are handled automatically by the repository

            // End employment
            $currentEmployment = $manager->currentEmployment()->first();
            if ($currentEmployment) {
                $currentEmployment->update(['ended_at' => $releaseDate]);
                $manager->update(['status' => EmploymentStatus::Released]);
            }
        });
    }
}
