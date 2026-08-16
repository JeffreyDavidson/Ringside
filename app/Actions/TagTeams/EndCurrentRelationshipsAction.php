<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Lifecycle\ChampionshipReignManager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamManager;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use Illuminate\Support\Carbon;

class EndCurrentRelationshipsAction
{
    public function __construct(private readonly ChampionshipReignManager $championshipReigns) {}

    public function handle(TagTeam $tagTeam, Carbon $effectiveDate): void
    {
        TagTeamWrestler::query()
            ->forTagTeamId($tagTeam->id)
            ->current()
            ->update(['left_at' => $effectiveDate]);

        TagTeamManager::query()
            ->where('tag_team_id', $tagTeam->id)
            ->current()
            ->update(['fired_at' => $effectiveDate]);

        $this->championshipReigns->endCurrentReignsForChampion($tagTeam, $effectiveDate);
    }
}
