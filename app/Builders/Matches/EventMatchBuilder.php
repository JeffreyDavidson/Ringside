<?php

declare(strict_types=1);

namespace App\Builders\Matches;

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @template TModel of EventMatch
 *
 * @extends Builder<TModel>
 */
class EventMatchBuilder extends Builder
{
    public function forEventId(int $eventId): static
    {
        $this->where('event_id', $eventId);

        return $this;
    }

    /**
     * @param  Collection<int, int>  $eventIds
     */
    public function forEventIds(Collection $eventIds): static
    {
        $this->whereIn('event_id', $eventIds);

        return $this;
    }

    public function forPastEvents(): static
    {
        self::constrainToPastEvents($this);
        $this->with('event');

        return $this;
    }

    public function forHistory(): static
    {
        return $this
            ->forPastEvents()
            ->with([
                'referees',
                'titles',
                'competitors.competitor',
                'competitors.side',
                'winningSide.competitors.competitor',
            ])
            ->latestEventFirst();
    }

    /**
     * @template TRelatedModel of Model
     *
     * @param  Builder<TRelatedModel>  $query
     */
    public static function constrainToPastEvents(Builder $query): void
    {
        $query->whereRelation('event', 'date', '<', now());
    }

    public function forCompetitor(Wrestler|TagTeam $competitor): static
    {
        $this->whereHas('competitors', function (Builder $query) use ($competitor): void {
            $query->whereMorphedTo('competitor', $competitor);
        })->with('competitors');

        return $this;
    }

    public function forWrestlerId(int $wrestlerId): static
    {
        return $this->forCompetitorId($wrestlerId, Wrestler::class);
    }

    public function forTagTeamId(int $tagTeamId): static
    {
        return $this->forCompetitorId($tagTeamId, TagTeam::class);
    }

    /**
     * @param  class-string<TagTeam|Wrestler>  $competitorType
     */
    private function forCompetitorId(int $competitorId, string $competitorType): static
    {
        $this->whereHas('competitors', function (Builder $query) use ($competitorId, $competitorType): void {
            $query->whereHasMorph(
                'competitor',
                $competitorType,
                function (Builder $query) use ($competitorId): void {
                    $query->whereKey($competitorId);
                },
            );
        })->with('competitors');

        return $this;
    }

    public function forReferee(Referee $referee): static
    {
        return $this->forRefereeId($referee->id);
    }

    public function forRefereeId(int $refereeId): static
    {
        $this->whereHas('referees', function (Builder $query) use ($refereeId): void {
            $query->whereKey($refereeId);
        })->with('referees');

        return $this;
    }

    /**
     * @param  Collection<int, int>  $refereeIds
     */
    public function withAnyRefereeIds(Collection $refereeIds): static
    {
        $this->whereHas('referees', function (Builder $query) use ($refereeIds): void {
            $query->whereKey($refereeIds);
        });

        return $this;
    }

    /**
     * @param  Collection<int, int>  $titleIds
     */
    public function withAnyTitleIds(Collection $titleIds): static
    {
        $this->whereHas('titles', function (Builder $query) use ($titleIds): void {
            $query->whereKey($titleIds);
        });

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
        )
            ->orderByDesc($this->qualifyColumn('event_id'))
            ->orderBy($this->qualifyColumn('match_number'));

        return $this;
    }
}
