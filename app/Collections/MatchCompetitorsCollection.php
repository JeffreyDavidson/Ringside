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
    /** @return BaseCollection<int, Wrestler> */
    public function wrestlers(): BaseCollection
    {
        return $this->competitorModels()
            ->filter(fn (Wrestler|TagTeam $competitor): bool => $competitor instanceof Wrestler)
            ->values();
    }

    /** @return BaseCollection<int, TagTeam> */
    public function tagTeams(): BaseCollection
    {
        return $this->competitorModels()
            ->filter(fn (Wrestler|TagTeam $competitor): bool => $competitor instanceof TagTeam)
            ->values();
    }

    /**
     * Determine whether a polymorphic competitor identity is present.
     */
    public function containsCompetitor(string $competitorType, int $competitorId): bool
    {
        return $this->contains(fn (MatchCompetitor $competitor): bool => $competitor->competitor_type === $competitorType
            && $competitor->competitor_id === $competitorId);
    }

    /**
     * The entries of each side, keyed by the side's position in ascending order. Reads each entry's loaded `side`.
     *
     * @return BaseCollection<int, BaseCollection<int, MatchCompetitor>>
     */
    public function bySidePosition(): BaseCollection
    {
        return $this->toBase()
            ->groupBy(fn (MatchCompetitor $competitor): int => $competitor->side->position)
            ->sortKeys();
    }

    /** @return BaseCollection<int, BaseCollection<int, Wrestler|TagTeam>> */
    public function competitorModelsBySidePosition(): BaseCollection
    {
        return $this->bySidePosition()
            ->map(fn (BaseCollection $competitors): BaseCollection => $competitors
                ->map(fn (MatchCompetitor $competitor): Wrestler|TagTeam => $competitor->competitor)
                ->values());
    }

    /**
     * The loaded wrestler or tag team of each entry, in entry order.
     *
     * @return BaseCollection<int, Wrestler|TagTeam>
     */
    public function competitorModels(): BaseCollection
    {
        return $this->toBase()->map(fn (MatchCompetitor $competitor): Wrestler|TagTeam => $competitor->competitor);
    }
}
