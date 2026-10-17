<?php

declare(strict_types=1);

namespace App\Queries\Roster;

use App\Builders\Roster\ManagerBuilder;
use App\Models\Roster\Managers\Manager;

final class PreviousManagersQuery
{
    /** @return ManagerBuilder<Manager> */
    public function get(int $stableId): ManagerBuilder
    {
        return Manager::query()
            ->whereFormerManagerOfStable($stableId)
            ->orderBy('managers.last_name')
            ->orderBy('managers.first_name');
    }
}
