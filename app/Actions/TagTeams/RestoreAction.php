<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Models\Roster\TagTeams\TagTeam;
use App\Services\Roster\TagTeams\TagTeamDeletionService;

class RestoreAction
{
    public function __construct(
        private readonly TagTeamDeletionService $deletion,
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
        $this->deletion->restore($tagTeam, now());
    }
}
