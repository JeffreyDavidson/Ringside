<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Lifecycle\Periods\OpenPeriodEnder;
use App\Models\Roster\Managers\Manager;
use Illuminate\Support\Carbon;

class EndManagerAssignmentsForManagerAction
{
    public function handle(Manager $manager, Carbon $date): void
    {
        OpenPeriodEnder::end($manager->wrestlers()->newPivotQuery(), 'hired_at', 'fired_at', $date);
        OpenPeriodEnder::end($manager->tagTeams()->newPivotQuery(), 'hired_at', 'fired_at', $date);
    }
}
