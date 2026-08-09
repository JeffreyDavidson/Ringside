<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Models\Stables\StableWrestler;
use App\Models\TagTeams\TagTeamWrestler;
use App\Models\Wrestlers\Wrestler;
use App\Models\Wrestlers\WrestlerManager;
use Illuminate\Support\Carbon;

class EndCurrentRelationshipsAction
{
    public function handle(Wrestler $wrestler, Carbon $effectiveDate): void
    {
        TagTeamWrestler::query()
            ->where('wrestler_id', $wrestler->id)
            ->whereNull('left_at')
            ->update(['left_at' => $effectiveDate]);

        StableWrestler::query()
            ->where('wrestler_id', $wrestler->id)
            ->whereNull('left_at')
            ->update(['left_at' => $effectiveDate]);

        WrestlerManager::query()
            ->where('wrestler_id', $wrestler->id)
            ->whereNull('fired_at')
            ->update(['fired_at' => $effectiveDate]);

        $wrestler->currentChampionships()->update([
            'lost_at' => $effectiveDate,
        ]);
    }
}
