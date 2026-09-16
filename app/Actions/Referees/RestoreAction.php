<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Lifecycle\Periods\DeletionStateManager;
use App\Lifecycle\Roster\Individuals\IndividualDeletionEligibility;
use App\Models\Roster\Referees\Referee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RestoreAction
{
    public function __construct(
        private readonly DeletionStateManager $deletionState,
        private readonly IndividualDeletionEligibility $eligibility,
    ) {}

    /**
     * Restore a soft-deleted referee.
     *
     * This handles the complete referee restoration workflow:
     * - Restores the soft-deleted referee record
     * - Makes the referee available for future employment and match officiating
     * - Preserves all historical employment, injury, suspension, and match records
     * - Does not automatically restore employment relationships
     * - Requires separate employment action to make referee active again
     *
     * @param  Referee  $referee  The soft-deleted referee to restore
     */
    public function handle(Referee $referee, ?Carbon $restoreDate = null): void
    {
        $effectiveDate = $restoreDate ?? now();

        DB::transaction(function () use ($referee, $effectiveDate): void {
            $lockedReferee = $referee->refreshForUpdate();

            $this->eligibility->ensureCanRestore($lockedReferee);
            $this->deletionState->restore($lockedReferee, $effectiveDate);
        });
    }
}
