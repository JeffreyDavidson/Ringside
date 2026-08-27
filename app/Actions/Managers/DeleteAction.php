<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Roster\Individuals\IndividualDeletionService;
use Illuminate\Support\Carbon;

class DeleteAction
{
    public function __construct(
        private readonly IndividualDeletionService $deletion,
        private readonly EndCurrentRelationshipsAction $endCurrentRelationships,
    ) {}

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
     * - Ends active employment, retirement, suspension, and injury periods
     * - Preserves manager employment history for administrative records
     *
     * OTHER CLEANUP:
     * - Soft deletes the manager record
     * - Allows for future restoration if needed
     * - Maintains referential integrity with historical data
     *
     * @param  Manager  $manager  The manager to delete
     * @param  Carbon|null  $deletionDate  The deletion date (defaults to now)
     */
    public function handle(Manager $manager, ?Carbon $deletionDate = null): void
    {
        $this->deletion->delete(
            $manager,
            $deletionDate ?? now(),
            function (Wrestler|Manager|Referee $lockedIndividual, Carbon $date): void {
                if (! $lockedIndividual instanceof Manager) {
                    return;
                }

                $this->endCurrentRelationships->handle($lockedIndividual, $date);
            },
        );
    }
}
