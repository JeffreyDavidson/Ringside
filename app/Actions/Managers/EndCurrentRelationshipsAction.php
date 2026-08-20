<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeamManager;
use App\Models\Roster\Wrestlers\WrestlerManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EndCurrentRelationshipsAction
{
    public function handle(Manager $manager, Carbon $effectiveDate): void
    {
        DB::transaction(function () use ($manager, $effectiveDate): void {
            $lockedManager = Manager::query()
                ->whereKey($manager->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            WrestlerManager::query()
                ->forManagerId($lockedManager->id)
                ->current()
                ->update(['fired_at' => $effectiveDate]);

            TagTeamManager::query()
                ->forManagerId($lockedManager->id)
                ->current()
                ->update(['fired_at' => $effectiveDate]);
        });
    }
}
