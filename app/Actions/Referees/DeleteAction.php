<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Models\Referees\Referee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteAction extends BaseRefereeAction
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
        $deletionDate = $this->getEffectiveDate($deletionDate);

        DB::transaction(function () use ($referee, $deletionDate): void {
            // Handle referee status - employed referees can be suspended/injured, retired referees are not employed
            if ($referee->isEmployed()) {
                // End suspension or injury if active (employed referee cannot be both)
                if ($referee->isSuspended()) {
                    $this->refereeRepository->endSuspension($referee, $deletionDate);
                } elseif ($referee->isInjured()) {
                    $this->refereeRepository->endInjury($referee, $deletionDate);
                }

                // End employment
                $this->refereeRepository->endEmployment($referee, $deletionDate);
            } elseif ($referee->isRetired()) {
                // End retirement if active (retired referees are not employed)
                $this->refereeRepository->endRetirement($referee, $deletionDate);
            }

            // Soft delete the referee record
            $this->refereeRepository->delete($referee);
        });
    }
}
