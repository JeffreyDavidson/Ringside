<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Models\Referees\Referee;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RestoreAction extends BaseRefereeAction
{
    use AsAction;

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
     *
     * @example
     * ```php
     * $deletedReferee = Referee::onlyTrashed()->find(1);
     * RestoreAction::run($deletedReferee);
     * ```
     */
    public function handle(Referee $referee): void
    {
        DB::transaction(function () use ($referee): void {
            $this->refereeRepository->restore($referee);

            // Note: No automatic relationship restoration to avoid conflicts.
            // All employment relationships must be re-established explicitly using separate actions.
        });
    }
}
