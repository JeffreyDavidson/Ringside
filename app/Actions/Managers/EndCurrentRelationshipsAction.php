<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeamManager;
use App\Models\Roster\Wrestlers\WrestlerManager;
use Illuminate\Support\Carbon;

class EndCurrentRelationshipsAction
{
    public function handle(Manager $manager, Carbon $effectiveDate): void
    {
        WrestlerManager::query()
            ->forManagerId($manager->id)
            ->current()
            ->update(['fired_at' => $effectiveDate]);

        TagTeamManager::query()
            ->forManagerId($manager->id)
            ->current()
            ->update(['fired_at' => $effectiveDate]);
    }
}
