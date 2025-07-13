<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

/**
 * Contract for repositories that manage tag team membership.
 *
 * This interface defines the methods required for repositories
 * that handle adding/removing wrestlers from tag teams.
 */
interface ManagesTagTeamMembers
{
    /**
     * Add a wrestler to the tag team.
     */
    public function addWrestler(TagTeam $tagTeam, Wrestler $wrestler, Carbon $joinDate): void;

    /**
     * Remove a wrestler from the tag team.
     */
    public function removeWrestler(TagTeam $tagTeam, Wrestler $wrestler, Carbon $removalDate): void;
}
