<?php

declare(strict_types=1);

namespace App\Builders\Matches;

use App\Enums\Matches\MatchFinish;
use App\Enums\Matches\MatchType;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @template TModel of EventMatch
 *
 * @extends Builder<TModel>
 */
class EventMatchBuilder extends Builder
{
    /** Earlier source bookings in one promotion; date passage alone does not prove a result. */
    public function forPromotionHistory(int $promotionId, Carbon $before, ?int $excludedMatchId = null): static
    {
        self::constrainToPromotionHistory($this, $promotionId, $before, $excludedMatchId);

        return $this;
    }

    /** @param Builder<*> $query */
    public static function constrainToPromotionHistory(Builder $query, int $promotionId, Carbon $before, ?int $excludedMatchId = null): void
    {
        $query->whereHas('event', fn (Builder $event): Builder => $event
            ->where('promotion_id', $promotionId)->where('date', '<', $before));

        if ($excludedMatchId !== null) {
            $query->whereKeyNot($excludedMatchId);
        }
    }

    /** Compare persisted opposing sides without attributing a partner or multi-way loss to a direct opponent. */
    public function opposedTo(Wrestler|TagTeam $subject, Wrestler|TagTeam $opponent): static
    {
        return $this->whereExists(fn (QueryBuilder $query): QueryBuilder => $query
            ->selectRaw('1')
            ->from('events_matches_competitors as history_subject')
            ->join('events_matches_competitors as history_opponent', 'history_subject.match_id', '=', 'history_opponent.match_id')
            ->whereColumn('history_subject.match_id', 'events_matches.id')
            ->whereColumn('history_subject.match_side_id', '<>', 'history_opponent.match_side_id')
            ->where('history_subject.competitor_type', $subject->getMorphClass())
            ->where('history_subject.competitor_id', $subject->id)
            ->where('history_opponent.competitor_type', $opponent->getMorphClass())
            ->where('history_opponent.competitor_id', $opponent->id));
    }

    public function directMeetings(Wrestler|TagTeam $first, Wrestler|TagTeam $second): static
    {
        $types = $first instanceof Wrestler
            ? [MatchType::Singles]
            : [MatchType::TagTeam, MatchType::TornadoTagTeam, MatchType::SixManTagTeam, MatchType::EightManTagTeam, MatchType::TenManTagTeam];

        return $this->opposedTo($first, $second)
            ->whereIn('match_type', $types)
            ->has('competitors', '=', 2)
            ->has('sides', '=', 2);
    }

    /**
     * Project known results without hydrating an entire career. A finish with malformed winner metadata is unknown.
     *
     * @param  Builder<*>  $query
     */
    public static function addRecordedResultFlag(Builder $query): void
    {
        $query->addSelect('events_matches.*')->selectRaw(<<<'SQL'
            CASE WHEN ((events_matches.match_finish IN (?, ?) AND events_matches.winning_side_id IS NULL) OR
                (events_matches.match_finish IN (?, ?, ?, ?, ?, ?, ?) AND EXISTS (
                    SELECT 1 FROM events_matches_sides AS outcome_side
                    WHERE outcome_side.id = events_matches.winning_side_id AND outcome_side.match_id = events_matches.id
                    AND EXISTS (SELECT 1 FROM events_matches_competitors AS outcome_competitor
                        WHERE outcome_competitor.match_side_id = outcome_side.id AND outcome_competitor.match_id = events_matches.id))))
                AND (SELECT COUNT(DISTINCT structural_entry.match_side_id) FROM events_matches_competitors AS structural_entry
                    INNER JOIN events_matches_sides AS structural_side ON structural_side.id = structural_entry.match_side_id
                        AND structural_side.match_id = structural_entry.match_id
                    WHERE structural_entry.match_id = events_matches.id) >= 2
                AND NOT EXISTS (SELECT 1 FROM events_matches_competitors AS owned_entry
                    WHERE owned_entry.match_id = events_matches.id AND NOT (
                        (owned_entry.competitor_type = ? AND EXISTS (SELECT 1 FROM wrestlers
                            WHERE wrestlers.id = owned_entry.competitor_id
                            AND wrestlers.promotion_id = (SELECT promotion_id FROM events WHERE events.id = events_matches.event_id))) OR
                        (owned_entry.competitor_type = ? AND EXISTS (SELECT 1 FROM tag_teams
                            WHERE tag_teams.id = owned_entry.competitor_id
                            AND tag_teams.promotion_id = (SELECT promotion_id FROM events WHERE events.id = events_matches.event_id)))))
                THEN 1 ELSE 0 END AS history_recorded
            SQL, [
            MatchFinish::TimeLimitDraw->value, MatchFinish::NoDecision->value,
            MatchFinish::Pinfall->value, MatchFinish::Submission->value, MatchFinish::Knockout->value,
            MatchFinish::Stipulation->value, MatchFinish::Forfeit->value, MatchFinish::Disqualification->value, MatchFinish::Countout->value,
            (new Wrestler)->getMorphClass(), (new TagTeam)->getMorphClass(),
        ]);
    }

    public function withRecordedResultFlag(): static
    {
        self::addRecordedResultFlag($this);

        return $this;
    }

    /**
     * These matches as plain rows aliased `history`, each carrying `history_recorded` (a complete recorded result)
     * and `history_date` (the event date), ready to join to the competitor entries and aggregate. The builder itself is
     * left unchanged.
     */
    public function asHistorySource(): QueryBuilder
    {
        return DB::query()->fromSub(
            (clone $this)->withRecordedResultFlag()
                ->join('events as history_event', 'history_event.id', '=', 'events_matches.event_id')
                ->addSelect('history_event.date as history_date')
                ->toBase(),
            'history',
        );
    }

    /**
     * Keep the rows whose competitor entry (joined as `$alias`) is one of the selected wrestlers or tag teams.
     *
     * @param  list<array{type: string, id: int, side: int}>  $selection
     */
    public static function whereSelectedCompetitors(QueryBuilder $query, string $alias, array $selection): void
    {
        $query->where(function (QueryBuilder $query) use ($alias, $selection): void {
            foreach (array_unique(array_column($selection, 'type')) as $type) {
                $ids = array_column(array_filter($selection, fn (array $entry): bool => $entry['type'] === $type), 'id');
                $query->orWhere(fn (QueryBuilder $kind): QueryBuilder => $kind->where("{$alias}.competitor_type", $type)->whereIn("{$alias}.competitor_id", $ids));
            }
        });
    }

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

    /** Matches in card order: by match number, with the id breaking ties. */
    public function inCardOrder(): static
    {
        return $this->orderBy('events_matches.match_number')->orderBy('events_matches.id');
    }

    public function forPastEvents(): static
    {
        self::constrainToPastEvents($this);
        $this->with('event');

        return $this;
    }

    public function forHistory(): static
    {
        $this->with('event.promotion');

        return $this
            ->forPastEvents()
            ->withDisplayRelations()
            ->latestEventFirst();
    }

    /**
     * Eager load everything a match row lists (referees, titles, competitors and winners), each in id order so the
     * names always appear in the same sequence.
     */
    public function withDisplayRelations(): static
    {
        $this->with([
            'event',
            'referees' => fn (Relation $referees): Relation => $referees->orderBy('referees.id'),
            'titles' => fn (Relation $titles): Relation => $titles->orderBy('titles.id'),
            'competitors' => fn (Relation $competitors): Relation => $competitors->orderBy('events_matches_competitors.id'),
            'competitors.competitor',
            'competitors.side',
            'winningSide.competitors' => fn (Relation $competitors): Relation => $competitors->orderBy('events_matches_competitors.id'),
            'winningSide.competitors.competitor',
        ]);

        return $this;
    }

    /**
     * @param  Builder<EventMatch>  $query
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

    public function forRosterMember(Wrestler|Referee|TagTeam $member): static
    {
        return match (true) {
            $member instanceof Wrestler => $this->forWrestlerId($member->id),
            $member instanceof TagTeam => $this->forTagTeamId($member->id),
            default => $this->forRefereeId($member->id),
        };
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

    /**
     * Limit to matches that are still live: no recorded result, or an event that has not yet happened.
     */
    public function upcomingOrUnresulted(): static
    {
        $this->where(function (Builder $query): void {
            $query->whereNull('match_finish')
                ->orWhereRelation('event', 'date', '>=', now());
        });

        return $this;
    }

    public function forReferee(Referee $referee): static
    {
        return $this->forRefereeId($referee->id);
    }

    public function forRefereeId(int $refereeId): static
    {
        $this->whereRelation('referees', 'referees.id', $refereeId)
            ->with('referees');

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
        $event = new Event;

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
