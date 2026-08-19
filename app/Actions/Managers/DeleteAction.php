<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Lifecycle\DeletionPeriodCloser;
use App\Lifecycle\DeletionStateManager;
use App\Lifecycle\IndividualDeletionEligibility;
use App\Models\Roster\Managers\Manager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    public function __construct(
        private readonly DeletionPeriodCloser $periods,
        private readonly DeletionStateManager $deletionState,
        private readonly EndCurrentRelationshipsAction $endCurrentRelationships,
        private readonly IndividualDeletionEligibility $eligibility,
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
        $this->eligibility->ensureCanDelete($manager);

        $deletionDate = $deletionDate ?? now();

        DB::transaction(function () use ($manager, $deletionDate): void {
            $this->periods->close($manager, $deletionDate);
            $this->endCurrentRelationships->handle($manager, $deletionDate);

            // Soft delete the manager record
            $this->deletionState->delete($manager, $deletionDate);
        });
    }
}
