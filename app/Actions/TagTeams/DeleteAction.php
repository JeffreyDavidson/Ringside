<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Lifecycle\DeletionStateManager;
use App\Models\TagTeams\TagTeam;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    public function __construct(
        private readonly EndCurrentRelationshipsAction $endCurrentRelationships,
        private readonly DeletionStateManager $deletionState,
    ) {}

    /**
     * Delete a tag team.
     *
     * This handles the complete deletion workflow with business impact:
     *
     * PARTNERSHIP IMPACT:
     * - Ends all current wrestler partnerships (wrestlers become singles competitors)
     * - Ends all current manager relationships
     * - Preserves partnership history for reporting
     * - No impact on individual wrestler/manager employment status
     *
     * EMPLOYMENT IMPACT:
     * - Deletion validation rejects employed or suspended tag teams
     * - Retired tag teams remain retired (no artificial status changes)
     * - Does not affect individual member employment (they continue careers)
     * - Preserves tag team employment history for administrative records
     *
     * CHAMPIONSHIP IMPACT:
     * - Vacates any tag team championships held
     * - Preserves championship history and lineage
     *
     * OTHER CLEANUP:
     * - Soft deletes the tag team record
     * - Allows for future restoration if needed
     * - Maintains referential integrity with historical data
     *
     * NOTE ON DELETION TYPE:
     * This performs soft deletion only - the tag team record is marked as deleted
     * but preserved for historical reporting, championship lineage, and administrative purposes.
     *
     * @param  TagTeam  $tagTeam  The tag team to delete
     * @param  Carbon|null  $deletionDate  The deletion date (defaults to now)
     */
    public function handle(TagTeam $tagTeam, ?Carbon $deletionDate = null): void
    {
        $tagTeam->ensureCanBeDeleted();

        $deletionDate = DateHelper::resolveDate($deletionDate);

        DB::transaction(function () use ($tagTeam, $deletionDate): void {
            $this->endCurrentRelationships->handle($tagTeam, $deletionDate);

            // Soft delete the tag team record
            $this->deletionState->delete($tagTeam, $deletionDate);
        });
    }
}
