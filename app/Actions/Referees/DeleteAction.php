<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Enums\Shared\EmploymentStatus;
use App\Helpers\DateHelper;
use App\Models\Referees\Referee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteAction
{
    use AsAction;

    /**
     * Delete a referee.
     *
     * This handles the complete deletion workflow with business impact:
     *
     * EMPLOYMENT IMPACT:
     * - Ends employment, suspension, and injury if active
     * - Ends retirement if currently retired
     * - Preserves employment history for reporting
     *
     * MATCH OFFICIATING IMPACT:
     * - Removes referee from active match assignments
     * - Preserves historical match officiating records
     * - No impact on past match results or statistics
     *
     * OTHER CLEANUP:
     * - Soft deletes the referee record
     * - Allows for future restoration if needed
     * - Maintains referential integrity with historical data
     *
     * @param  Referee  $referee  The referee to delete
     * @param  Carbon|null  $deletionDate  The deletion date (defaults to now)
     *
     * @example
     * ```php
     * $referee = Referee::find(1);
     * DeleteAction::run($referee);
     * ```
     */
    public function handle(Referee $referee, ?Carbon $deletionDate = null): void
    {
        $deletionDate = DateHelper::resolveDate($deletionDate);

        DB::transaction(function () use ($referee, $deletionDate): void {
            // Handle referee status - employed referees can be suspended/injured, retired referees are not employed
            if ($referee->isEmployed()) {
                // End suspension or injury if active (employed referee cannot be both)
                if ($referee->isSuspended()) {
                    $currentSuspension = $referee->currentSuspension()->first();
                    if ($currentSuspension) {
                        $currentSuspension->update(['ended_at' => $deletionDate]);
                    }
                } elseif ($referee->isInjured()) {
                    $currentInjury = $referee->currentInjury()->first();
                    if ($currentInjury) {
                        $currentInjury->update(['ended_at' => $deletionDate->toDateTimeString()]);
                    }
                }

                // End employment
                $currentEmployment = $referee->currentEmployment()->first();
                if ($currentEmployment) {
                    $currentEmployment->update(['ended_at' => $deletionDate]);
                    $referee->update(['status' => EmploymentStatus::Released]);
                }
            } elseif ($referee->isRetired()) {
                // End retirement if active (retired referees are not employed)
                $currentRetirement = $referee->currentRetirement()->first();
                if ($currentRetirement) {
                    $currentRetirement->update(['ended_at' => $deletionDate]);
                }
            }

            // Soft delete the referee record
            $referee->delete();
        });
    }
}
