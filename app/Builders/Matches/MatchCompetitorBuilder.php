<?php

declare(strict_types=1);

namespace App\Builders\Matches;

use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
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
     * @param  class-string<TagTeam|Wrestler>  $competitorType
     * @param  Collection<int, int>  $competitorIds
     */
    public function forCompetitorIds(string $competitorType, Collection $competitorIds): static
    {
        $this->where('competitor_type', $competitorType)
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
