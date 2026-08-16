<?php

declare(strict_types=1);

namespace App\Collections;

use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

/** @extends Collection<int, MatchCompetitor> */
class MatchCompetitorsCollection extends Collection
{
    /** @return BaseCollection<int, BaseCollection<int, Wrestler|TagTeam>> */
    public function competitorModelsBySidePosition(): BaseCollection
    {
        return $this->toBase()
            ->groupBy(fn (MatchCompetitor $competitor): int => $competitor->side->position)
            ->sortKeys()
            ->map(fn (BaseCollection $competitors): BaseCollection => $competitors
                ->map(fn (MatchCompetitor $competitor): Wrestler|TagTeam => $competitor->competitor)
                ->values());
    }
}
