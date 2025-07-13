<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Models\TagTeams\TagTeam;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RestoreAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Restore a soft-deleted tag team.
     *
     * This handles the complete tag team restoration workflow:
     * - Restores the soft-deleted tag team record
     * - Makes the tag team available for future employment and competition
     * - Preserves all historical partnerships, employment, and championship records
     * - Does not automatically restore wrestler partnerships or manager relationships
     * - Requires separate actions to rebuild team membership
     * - Former members may have moved to other teams during deletion period
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
        DB::transaction(function () use ($tagTeam, $forceReunite): void {
            $this->tagTeamRepository->restore($tagTeam);
            $restorationDate = $this->getEffectiveDate();

            // Attempt to restore former members if available and not in other teams
            $this->restoreFormerMembers($tagTeam, $restorationDate, $forceReunite, $this->tagTeamRepository);

            // Note: No automatic employment restoration to avoid conflicts.
            // All employment relationships must be re-established explicitly using separate actions.
        });
    }
}
