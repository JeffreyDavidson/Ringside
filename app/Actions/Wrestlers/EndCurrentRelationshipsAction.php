<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Lifecycle\ChampionshipReignManager;
use App\Models\Stables\StableWrestler;
use App\Models\TagTeams\TagTeamWrestler;
use App\Models\Wrestlers\Wrestler;
use App\Models\Wrestlers\WrestlerManager;
use Illuminate\Support\Carbon;

class EndCurrentRelationshipsAction
{
    public function __construct(private readonly ChampionshipReignManager $championshipReigns) {}

    public function handle(Wrestler $wrestler, Carbon $effectiveDate): void
    {
        TagTeamWrestler::query()
            ->forWrestlerId($wrestler->id)
            ->current()
            ->update(['left_at' => $effectiveDate]);

        StableWrestler::query()
            ->where('wrestler_id', $wrestler->id)
            ->current()
            ->update(['left_at' => $effectiveDate]);

        WrestlerManager::query()
            ->where('wrestler_id', $wrestler->id)
            ->current()
            ->update(['fired_at' => $effectiveDate]);

        $this->championshipReigns->endCurrentReignsForChampion($wrestler, $effectiveDate);
    }
}
