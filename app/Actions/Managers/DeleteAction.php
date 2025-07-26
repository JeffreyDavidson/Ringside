<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Enums\Shared\EmploymentStatus;
use App\Models\Managers\Manager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteAction
{
    use AsAction;

    /**
     * Delete a manager.
     *
     * This handles the complete deletion workflow with business impact:
     *
     * MANAGEMENT IMPACT:
     * - Ends all current wrestler and tag team management relationships
     * - Preserves management history for reporting
     * - No impact on past management records or statistics
     *
     * EMPLOYMENT IMPACT:
     * - Ends employment, suspension, and injury if active
     * - Ends retirement if currently retired
     * - Preserves employment history for administrative records
     *
     * OTHER CLEANUP:
     * - Soft deletes the manager record
     * - Allows for future restoration if needed
     * - Maintains referential integrity with historical data
     *
     * @param  Manager  $manager  The manager to delete
     * @param  Carbon|null  $deletionDate  The deletion date (defaults to now)
     *
     * @example
     * ```php
     * // Delete manager immediately
     * $manager = Manager::find(1);
     * DeleteAction::run($manager);
     *
     * // Delete with specific date
     * DeleteAction::run($manager, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(Manager $manager, ?Carbon $deletionDate = null): void
    {
        $deletionDate = $deletionDate ?? now();

        DB::transaction(function () use ($manager, $deletionDate): void {
            // Handle manager status - employed managers can be suspended/injured, retired managers are not employed
            if ($manager->isEmployed()) {
                // End suspension or injury if active (employed manager cannot be both)
                if ($manager->isSuspended()) {
                    $currentSuspension = $manager->currentSuspension()->first();
                    if ($currentSuspension) {
                        $currentSuspension->update(['ended_at' => $deletionDate]);
                    }
                } elseif ($manager->isInjured()) {
                    $currentInjury = $manager->currentInjury()->first();
                    if ($currentInjury) {
                        $currentInjury->update(['ended_at' => $deletionDate->toDateTimeString()]);
                    }
                }

                // End employment
                $currentEmployment = $manager->currentEmployment()->first();
                if ($currentEmployment) {
                    $currentEmployment->update(['ended_at' => $deletionDate]);
                    $manager->update(['status' => EmploymentStatus::Released]);
                }
            } elseif ($manager->isRetired()) {
                // End retirement if active (retired managers are not employed)
                $currentRetirement = $manager->currentRetirement()->first();
                if ($currentRetirement) {
                    $currentRetirement->update(['ended_at' => $deletionDate]);
                }
            }

            // End current management relationships
            // Note: Management relationships are handled automatically by the repository

            // Soft delete the manager record
            $manager->delete();
        });
    }
}
