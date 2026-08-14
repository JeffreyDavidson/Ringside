<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Models\TagTeams\TagTeam;
use App\Models\TagTeams\TagTeamManager;
use App\Models\TagTeams\TagTeamWrestler;
use Illuminate\Support\Carbon;

class EndCurrentRelationshipsAction
{
    public function handle(TagTeam $tagTeam, Carbon $effectiveDate): void
    {
        TagTeamWrestler::query()
            ->where('tag_team_id', $tagTeam->id)
            ->current()
            ->update(['left_at' => $effectiveDate]);

        TagTeamManager::query()
            ->where('tag_team_id', $tagTeam->id)
            ->current()
            ->update(['fired_at' => $effectiveDate]);
    }
}
