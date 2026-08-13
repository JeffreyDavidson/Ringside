<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Lifecycle\DeletionStateManager;
use App\Models\TagTeams\TagTeam;
use Illuminate\Support\Facades\DB;

class RestoreAction
{
    public function __construct(private readonly DeletionStateManager $deletionState) {}

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
        $tagTeam->ensureCanBeRestored();

        DB::transaction(function () use ($tagTeam): void {
            $this->deletionState->restore($tagTeam, now());
        });
    }
}
