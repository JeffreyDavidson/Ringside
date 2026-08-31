<?php

declare(strict_types=1);

namespace App\Builders\Matches;

use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @template TModel of MatchCompetitor
 *
 * @extends Builder<TModel>
 */
class MatchCompetitorBuilder extends Builder
{
    /**
     * @param  Collection<int, int>  $wrestlerIds
     */
    public function forWrestlerIds(Collection $wrestlerIds): static
    {
        return $this->forCompetitorIds(Wrestler::class, $wrestlerIds);
    }

    /**
     * @param  Collection<int, int>  $tagTeamIds
     */
    public function forTagTeamIds(Collection $tagTeamIds): static
    {
        return $this->forCompetitorIds(TagTeam::class, $tagTeamIds);
    }

    /**
     * @param  class-string<TagTeam|Wrestler>  $competitorType
     * @param  Collection<int, int>  $competitorIds
     */
    private function forCompetitorIds(string $competitorType, Collection $competitorIds): static
    {
        $this->where('competitor_type', (new $competitorType())->getMorphClass())
            ->whereIn('competitor_id', $competitorIds);

        return $this;
    }

    /**
     * @param  Collection<int, int>  $eventIds
     */
    public function forEventIds(Collection $eventIds): static
    {
        $this->whereIn(
            'match_id',
            EventMatch::query()
                ->whereIn('event_id', $eventIds)
                ->select('id'),
        );

        return $this;
    }
}
