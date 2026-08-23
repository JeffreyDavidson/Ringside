<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Lifecycle\ChampionshipReignManager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamManager;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EndCurrentRelationshipsAction
{
    public function __construct(private readonly ChampionshipReignManager $championshipReigns) {}

    public function handle(TagTeam $tagTeam, Carbon $effectiveDate): void
    {
        DB::transaction(function () use ($tagTeam, $effectiveDate): void {
            $lockedTagTeam = TagTeam::query()
                ->whereKey($tagTeam->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            TagTeamWrestler::query()
                ->forTagTeamId($lockedTagTeam->id)
                ->current()
                ->update(['left_at' => $effectiveDate]);

            TagTeamManager::query()
                ->whereBelongsTo($lockedTagTeam)
                ->current()
                ->update(['fired_at' => $effectiveDate]);

            $this->championshipReigns->endCurrentReignsForChampion($lockedTagTeam, $effectiveDate);
        });
    }
}
