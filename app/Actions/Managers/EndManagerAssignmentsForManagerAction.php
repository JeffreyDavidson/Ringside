<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Roster\Managers\Manager;
use Illuminate\Support\Carbon;

class EndManagerAssignmentsForManagerAction
{
    public function handle(Manager $manager, Carbon $date): void
    {
        $manager->wrestlers()->newPivotQuery()->whereNull('fired_at')->update(['fired_at' => $date]);
        $manager->tagTeams()->newPivotQuery()->whereNull('fired_at')->update(['fired_at' => $date]);
    }
}
