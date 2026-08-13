<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Lifecycle\DeletionPeriodCloser;
use App\Lifecycle\DeletionStateManager;
use App\Models\Referees\Referee;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    public function __construct(
        private readonly DeletionPeriodCloser $periods,
        private readonly DeletionStateManager $deletionState,
    ) {}

    /**
     * Delete a referee.
     *
     * This handles the complete deletion workflow with business impact:
     *
     * EMPLOYMENT IMPACT:
     * - Ends active employment, retirement, suspension, and injury periods
     * - Preserves referee employment history for administrative records
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
     */
    public function handle(Referee $referee, ?Carbon $deletionDate = null): void
    {
        $referee->ensureCanBeDeleted();

        $deletionDate = DateHelper::resolveDate($deletionDate);

        DB::transaction(function () use ($referee, $deletionDate): void {
            $this->periods->close($referee, $deletionDate);

            // Soft delete the referee record
            $this->deletionState->delete($referee, $deletionDate);
        });
    }
}
