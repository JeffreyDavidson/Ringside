<?php

declare(strict_types=1);

namespace App\Queries\Roster;

use App\Builders\Roster\StableBuilder;
use App\Models\Roster\Stables\Stable;

final class PreviousStablesQuery
{
    /** @return StableBuilder<Stable> */
    public function get(int $managerId): StableBuilder
    {
        return Stable::query()
            ->whereFormerlyManagedBy($managerId)
            ->orderBy('stables.name');
    }
}
