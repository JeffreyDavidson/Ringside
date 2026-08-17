<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Lifecycle\DeletionStateManager;
use App\Lifecycle\TagTeamDeletionEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Facades\DB;

class RestoreAction
{
    public function __construct(
        private readonly DeletionStateManager $deletionState,
        private readonly TagTeamDeletionEligibility $eligibility,
    ) {}

    /**
     * Restore a soft-deleted tag team.
     *
     * This handles the complete tag team restoration workflow:
     * - Validates the tag team can be restored (soft-deleted, no name conflicts)
     * - Restores the soft-deleted tag team record
     * - Makes the tag team available for future employment and competition
     * - Preserves all historical partnerships, employment, and championship records
     * - Preserves ended wrestler memberships as partnership history
     * - Employment relationships are not automatically restored to avoid conflicts
     */
    public function handle(TagTeam $tagTeam): void
    {
        DB::transaction(function () use ($tagTeam): void {
            $lockedTagTeam = TagTeam::query()->withTrashed()->whereKey($tagTeam->getKey())->lockForUpdate()->firstOrFail();
            $this->eligibility->ensureCanRestore($lockedTagTeam);

            $this->deletionState->restore($lockedTagTeam, now());
        });

        $tagTeam->setAttribute($tagTeam->getDeletedAtColumn(), null);
    }
}
