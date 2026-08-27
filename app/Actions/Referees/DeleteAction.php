<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Models\Roster\Referees\Referee;
use App\Services\Roster\Individuals\IndividualDeletionService;
use Illuminate\Support\Carbon;

class DeleteAction
{
    public function __construct(
        private readonly IndividualDeletionService $deletion,
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
        $this->deletion->delete($referee, $deletionDate ?? now());
    }
}
