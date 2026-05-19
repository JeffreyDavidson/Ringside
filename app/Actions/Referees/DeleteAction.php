<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Actions\Concerns\StatusTransitionPipeline;
use App\Models\Referees\Referee;
use App\Support\DateHelper;
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
     * - Uses StatusTransitionPipeline.delete() to end all active statuses
     * - Automatically handles employment, retirement, suspension, and injury ending
     * - Preserves referee employment history for administrative records
     *
     * MATCH OFFICIATING IMPACT:
     * - Removes referee from active match assignments
     * - Preserves historical match officiating records
     * - No impact on past match results or statistics
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline for consistent status handling, following the same
     * pattern as other referee actions.
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
        if (method_exists($referee, 'ensureCanBeDeleted')) {
            $referee->ensureCanBeDeleted();
        }

        $deletionDate = DateHelper::resolveDate($deletionDate);

        DB::transaction(function () use ($referee, $deletionDate): void {
            // Handle referee status cleanup using StatusTransitionPipeline
            StatusTransitionPipeline::delete($referee, $deletionDate)->execute();

            // Soft delete the referee record
            $referee->delete();
        });
    }
}
