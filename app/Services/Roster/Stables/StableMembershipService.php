<?php

declare(strict_types=1);

namespace App\Services\Roster\Stables;

use App\Data\Stables\StableMembershipData;
use App\Models\Roster\Stables\Stable;

final class StableMembershipService
{
    public function currentMembers(Stable $stable): StableMembershipData
    {
        return new StableMembershipData(
            wrestlers: $stable->currentWrestlers,
            tagTeams: $stable->currentTagTeams,
        );
    }
}
