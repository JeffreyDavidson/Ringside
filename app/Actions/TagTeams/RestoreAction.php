<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Concerns\RestoreCascadeStrategy;
use App\Models\TagTeams\TagTeam;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RestoreAction
{
    use AsAction;

    /**
     * Restore a soft-deleted tag team.
     *
     * This handles the complete tag team restoration workflow with cascade strategies:
     * - Validates the tag team can be restored (soft-deleted, no name conflicts)
     * - Restores the soft-deleted tag team record
     * - Makes the tag team available for future employment and competition
     * - Preserves all historical partnerships, employment, and championship records
     * - Optionally handles former member reunion using cascade strategies
     * - Force reunion removes wrestlers from current teams to rebuild original partnership
     * - Gentle reunion only restores available members not in other teams
     * - Employment relationships are not automatically restored to avoid conflicts
     *
     * ARCHITECTURAL PATTERN:
     * Uses RestoreCascadeStrategy for consistent relationship management during restoration.
     * Note: This doesn't use StatusTransitionPipeline as restoration is not a status transition
     * but a record recovery operation.
     *
     * @param  TagTeam  $tagTeam  The soft-deleted tag team to restore
     * @param  bool  $forceReunite  Whether to force wrestlers out of current teams (default: false)
     *
     * @example
     * ```php
     * $deletedTagTeam = TagTeam::onlyTrashed()->where('name', 'The Dudley Boyz')->first();
     * RestoreAction::run($deletedTagTeam);
     *
     * // Force reunion (removes wrestlers from current teams)
     * RestoreAction::run($deletedTagTeam, true);
     * ```
     */
    public function handle(TagTeam $tagTeam, bool $forceReunite = false): void
    {
        $tagTeam->ensureCanBeRestored();

        DB::transaction(function () use ($tagTeam, $forceReunite): void {
            // Restore the soft-deleted tag team record
            $tagTeam->restore();
            $restorationDate = now();

            // Handle former member reunion using cascade strategy
            RestoreCascadeStrategy::conditionalReunion($forceReunite)($tagTeam, $restorationDate, 'restore');

            // Note: No automatic employment restoration to avoid conflicts.
            // All employment relationships must be re-established explicitly using separate actions.
        });
    }
}
