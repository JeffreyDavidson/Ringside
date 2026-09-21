<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Models\Roster\Stables\StableTagTeam;
use App\Models\Roster\Stables\StableWrestler;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of StableTagTeam|StableWrestler|TagTeamWrestler
 *
 * @extends Builder<TModel>
 */
class MembershipPeriodBuilder extends Builder
{
    public function current(): static
    {
        $this->whereNull('left_at');

        return $this;
    }

    public function ended(): static
    {
        $this->whereNotNull('left_at');

        return $this;
    }

    public function mostRecentlyJoinedFirst(): static
    {
        $this->orderByDesc('joined_at');

        return $this;
    }

    public function forHistory(): static
    {
        return $this
            ->ended()
            ->mostRecentlyJoinedFirst();
    }
}
