<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Lifecycle\DeletionStateManager;
use App\Lifecycle\IndividualDeletionEligibility;
use App\Models\Roster\Referees\Referee;
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
    public function handle(Referee $referee): void
    {
        DB::transaction(function () use ($referee): void {
            $lockedReferee = Referee::query()
                ->withTrashed()
                ->whereKey($referee->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureCanRestore($lockedReferee);
            $this->deletionState->restore($lockedReferee, now());

            // Note: No automatic relationship restoration to avoid conflicts.
            // All employment relationships must be re-established explicitly using separate actions.
        });
    }
}
