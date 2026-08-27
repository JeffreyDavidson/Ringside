<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Models\Roster\Referees\Referee;
use App\Services\Roster\Individuals\IndividualRestoreService;

class RestoreAction
{
    public function __construct(
        private readonly IndividualRestoreService $restore,
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
        $this->restore->restore($referee, now());
    }
}
