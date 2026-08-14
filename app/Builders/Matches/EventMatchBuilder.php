<?php

declare(strict_types=1);

namespace App\Builders\Matches;

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @template TModel of EventMatch
 *
 * @extends Builder<TModel>
 */
class EventMatchBuilder extends Builder
{
    public function forPastEvents(): static
    {
        $this->withWhereHas('event', function (Builder|Relation $query): void {
            $query->where('date', '<', now());
        });

        return $this;
    }

    public function forCompetitor(Wrestler|TagTeam $competitor): static
    {
        $this->whereHas('competitors', function (Builder $query) use ($competitor): void {
            $query->whereMorphedTo('competitor', $competitor);
        })->with('competitors');

        return $this;
    }

    public function forReferee(Referee $referee): static
    {
        $this->whereHas('referees', function (Builder $query) use ($referee): void {
            $query->whereKey($referee->getKey());
        })->with('referees');

        return $this;
    }

    public function latestEventFirst(): static
    {
        $event = new Event();

        $this->orderByDesc(
            $event->newQuery()
                ->select('date')
                ->whereColumn(
                    $event->qualifyColumn('id'),
                    $this->getModel()->qualifyColumn('event_id'),
                )
        );

        return $this;
    }
}
