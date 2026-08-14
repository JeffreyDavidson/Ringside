<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Models\Stables\StableTagTeam;
use App\Models\Stables\StableWrestler;

/**
 * @template TModel of StableTagTeam|StableWrestler
 *
 * @extends MembershipPeriodBuilder<TModel>
 */
class StableMembershipBuilder extends MembershipPeriodBuilder
{
    public function forStableId(int $stableId): static
    {
        $this->where('stable_id', $stableId);

        return $this;
    }
}
