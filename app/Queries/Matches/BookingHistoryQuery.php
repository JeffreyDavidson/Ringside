<?php

declare(strict_types=1);

namespace App\Queries\Matches;

use App\Builders\Matches\EventMatchBuilder;
use App\Enums\Matches\MatchFinish;
use App\Enums\Matches\MatchType;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Promotions\Promotion;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use stdClass;

/**
 * Current retained source records, filtered by event time; this is not a reconstruction of past database revisions.
 *
 * @phpstan-type HistoryRow array{date: string, event: string, url: string, number: int, type: string, finish: string, recorded: bool, winnerSide: ?int, sides: list<array{name: string, side: int, deleted: bool}>}
 * @phpstan-type Subject array{key: string, name: string, url: ?string, deleted: bool, latestRecordedDate: ?string, recent: list<HistoryRow>}
 * @phpstan-type DirectRecord array{first: string, second: string, url: ?string, firstWins: int, secondWins: int, draws: int, neutral: int, incomplete: int, recorded: int}
 * @phpstan-type Notice array{first: string, second: string, url: ?string, recorded: int, incomplete: int, latestDate: string, event: string, number: int, sourceUrl: string, type: string, finish: string}
 * @phpstan-type Snapshot array{available: bool, reference: string, from: string, subjects: list<Subject>, direct: ?DirectRecord, notices: list<Notice>, totalNotices: int, page: int, pages: int}
 */
final class BookingHistoryQuery
{
    public const int RECENT_DAYS = 90;

    public const int PREVIEW_ROWS = 5;

    /**
     * @param  array<int, array{wrestlers?: array<int>, tag_teams?: array<int>}>  $sides
     * @return Snapshot
     */
    public function forEvent(Event $event, MatchType $type, array $sides, ?int $excludedMatchId = null, int $page = 1): array
    {
        $event->loadMissing('promotion');
        $checkedAt = now();
        $reference = $event->date instanceof Carbon && $event->date->lessThan($checkedAt) ? $event->date->copy() : $checkedAt;
        $from = $reference->copy()->setTimezone(Promotion::zoneOf($event->promotion))
            ->subDays(self::RECENT_DAYS)->setTimezone('UTC');
        $snapshot = [
            'available' => false, 'reference' => $reference->toIso8601String(), 'from' => $from->toIso8601String(),
            'subjects' => [], 'direct' => null, 'notices' => [], 'totalNotices' => 0, 'page' => 1, 'pages' => 1,
        ];

        if ($event->promotion_id === null) {
            return $snapshot;
        }

        $selection = $this->selection($type, $sides);
        $subjects = $this->subjects($selection, $event->promotion_id);
        $base = EventMatch::query()->forPromotionHistory($event->promotion_id, $reference, $excludedMatchId);
        $latest = $this->latestRecordedDates($base, $selection);

        // Eager loading applies a per-parent limit, including for polymorphic many-to-many relationships.
        foreach ($subjects->groupBy(fn (Wrestler|TagTeam $subject): string => $subject->getMorphClass()) as $group) {
            new Collection($group->all())->load(['matches' => function (Relation $matches) use ($event, $reference, $excludedMatchId): void {
                EventMatchBuilder::constrainToPromotionHistory($matches->getQuery(), $event->promotion_id, $reference, $excludedMatchId);
                EventMatchBuilder::addRecordedResultFlag($matches->getQuery());
                $matches->orderByDesc(Event::query()->select('date')->whereColumn('events.id', 'events_matches.event_id'))
                    ->orderByDesc('events_matches.event_id')->orderBy('events_matches.match_number')->orderBy('events_matches.id')
                    ->with(['event.promotion', 'competitors' => function (Relation $entries): void {
                        $entries->orderBy('events_matches_competitors.id');
                    }, 'competitors.side', 'competitors.competitor'])->limit(self::PREVIEW_ROWS);
            }]);
        }

        $snapshot['available'] = true;
        $snapshot['subjects'] = array_values($subjects->map(fn (Wrestler|TagTeam $subject): array => [
            'key' => $this->key($subject), 'name' => $subject->name, 'url' => $this->profileUrl($subject), 'deleted' => $subject->trashed(),
            'latestRecordedDate' => isset($latest[$this->key($subject)])
                ? Carbon::parse($latest[$this->key($subject)])->setTimezone(Promotion::zoneOf($event->promotion))->toDateString()
                : null,
            'recent' => array_values($subject->matches->map(fn (EventMatch $match): array => $this->row($match))->all()),
        ])->all());

        if (count($selection) === 2 && $selection[0]['side'] !== $selection[1]['side']
            && $selection[0]['type'] === $selection[1]['type'] && $this->directType($type, $selection[0]['type'])) {
            $snapshot['direct'] = $this->directRecord(clone $base, $subjects->firstOrFail(), $subjects->sole(fn (Wrestler|TagTeam $subject): bool => $this->key($subject) === $selection[1]['type'].':'.$selection[1]['id']));
        }

        $notices = $this->notices(clone $base, $selection, $from, $subjects, max(1, $page));

        return [...$snapshot, ...$notices];
    }

    /**
     * @param  array<int, array{wrestlers?: array<int>, tag_teams?: array<int>}>  $sides
     * @return list<array{type: string, id: int, side: int}>
     */
    private function selection(MatchType $type, array $sides): array
    {
        Validator::make(['competitors' => $sides], [
            'competitors' => ['required', 'array', 'list', 'size:'.($type->usesIndividualCompetitorSides() ? 1 : $type->numberOfSides())],
            'competitors.*' => ['required', 'array:wrestlers,tag_teams'],
            'competitors.*.wrestlers' => ['sometimes', 'array', 'list'],
            'competitors.*.tag_teams' => ['sometimes', 'array', 'list'],
            'competitors.*.wrestlers.*' => ['integer', 'min:1', 'distinct'],
            'competitors.*.tag_teams.*' => ['integer', 'min:1', 'distinct'],
        ])->validate();
        $selection = [];

        foreach ($sides as $side => $entries) {
            $before = count($selection);

            foreach (['wrestlers' => (new Wrestler)->getMorphClass(), 'tag_teams' => (new TagTeam)->getMorphClass()] as $kind => $alias) {
                foreach ($entries[$kind] ?? [] as $id) {
                    if (! in_array($alias, $type->getAllowedCompetitorTypes(), true)) {
                        throw ValidationException::withMessages(['history' => __('matches.history.selection_required')]);
                    }

                    $selection[] = ['type' => $alias, 'id' => (int) $id, 'side' => $type->usesIndividualCompetitorSides() ? count($selection) : $side];
                }
            }

            if (count($selection) === $before) {
                throw ValidationException::withMessages(['history' => __('matches.history.selection_required')]);
            }
        }

        if (count($selection) < $type->getMinimumCompetitors()) {
            throw ValidationException::withMessages(['history' => __('matches.history.selection_required')]);
        }

        return $selection;
    }

    /**
     * @param  list<array{type: string, id: int, side: int}>  $selection
     * @return Collection<int, Wrestler|TagTeam>
     */
    private function subjects(array $selection, int $promotionId): Collection
    {
        $wrestlerAlias = (new Wrestler)->getMorphClass();
        $teamAlias = (new TagTeam)->getMorphClass();
        $wrestlerIds = array_column(array_filter($selection, fn (array $entry): bool => $entry['type'] === $wrestlerAlias), 'id');
        $teamIds = array_column(array_filter($selection, fn (array $entry): bool => $entry['type'] === $teamAlias), 'id');
        $wrestlers = Wrestler::query()->withTrashed()->where('promotion_id', $promotionId)->whereKey($wrestlerIds)->with('promotion')->get();
        $teams = TagTeam::query()->withTrashed()->where('promotion_id', $promotionId)->whereKey($teamIds)->with('promotion')->get();
        // Eloquent merge keys by numeric id, which would collapse a wrestler and team with the same id.
        $keyed = collect([...$wrestlers, ...$teams])->keyBy(fn (Wrestler|TagTeam $subject): string => $this->key($subject));

        return new Collection(array_map(function (array $entry) use ($keyed): Wrestler|TagTeam {
            $subject = $keyed->get($entry['type'].':'.$entry['id']);

            if ($subject === null) {
                throw ValidationException::withMessages(['history' => __('matches.history.unavailable_selection')]);
            }

            return $subject;
        }, $selection));
    }

    /**
     * @param  EventMatchBuilder<EventMatch>  $base
     * @param  list<array{type: string, id: int, side: int}>  $selection
     * @return array<string, string>
     */
    private function latestRecordedDates(EventMatchBuilder $base, array $selection): array
    {
        $query = $this->sourceRows(clone $base)->join('events_matches_competitors as participant', 'participant.match_id', '=', 'history.id');
        $this->selected($query, 'participant', $selection);
        $rows = $query->where('history.history_recorded', 1)
            ->select('participant.competitor_type', 'participant.competitor_id')
            ->selectRaw('MAX(history.history_date) AS latest_date')->groupBy('participant.competitor_type', 'participant.competitor_id')->get();

        return $rows->mapWithKeys(fn (stdClass $row): array => [$row->competitor_type.':'.$row->competitor_id => (string) $row->latest_date])->all();
    }

    /** @param EventMatchBuilder<EventMatch> $base */
    private function sourceRows(EventMatchBuilder $base): QueryBuilder
    {
        return DB::query()->fromSub($base->withRecordedResultFlag()
            ->join('events as history_event', 'history_event.id', '=', 'events_matches.event_id')
            ->addSelect('history_event.date as history_date')->toBase(), 'history');
    }

    /** @param EventMatchBuilder<EventMatch> $base
     * @return DirectRecord
     */
    private function directRecord(EventMatchBuilder $base, Wrestler|TagTeam $first, Wrestler|TagTeam $second): array
    {
        $row = $this->sourceRows($base->directMeetings($first, $second))
            ->join('events_matches_competitors as direct_subject', 'direct_subject.match_id', '=', 'history.id')
            ->where('direct_subject.competitor_type', $first->getMorphClass())->where('direct_subject.competitor_id', $first->id)
            ->selectRaw('COUNT(CASE WHEN history.history_recorded = 1 THEN 1 END) AS recorded')
            ->selectRaw('COUNT(CASE WHEN history.history_recorded = 0 THEN 1 END) AS incomplete')
            ->selectRaw('COUNT(CASE WHEN history.history_recorded = 1 AND history.winning_side_id = direct_subject.match_side_id THEN 1 END) AS first_wins')
            ->selectRaw('COUNT(CASE WHEN history.history_recorded = 1 AND history.winning_side_id <> direct_subject.match_side_id THEN 1 END) AS second_wins')
            ->selectRaw('COUNT(CASE WHEN history.history_recorded = 1 AND history.match_finish = ? THEN 1 END) AS draws', [MatchFinish::TimeLimitDraw->value])
            ->selectRaw('COUNT(CASE WHEN history.history_recorded = 1 AND history.match_finish = ? THEN 1 END) AS neutral', [MatchFinish::NoDecision->value])->sole();

        return [
            'first' => $first->name, 'second' => $second->name, 'url' => $this->opponentUrl($first, $second),
            'recorded' => (int) $row->recorded, 'incomplete' => (int) $row->incomplete,
            'firstWins' => (int) $row->first_wins, 'secondWins' => (int) $row->second_wins,
            'draws' => (int) $row->draws, 'neutral' => (int) $row->neutral,
        ];
    }

    /** @param EventMatchBuilder<EventMatch> $base
     * @param  list<array{type: string, id: int, side: int}>  $selection
     * @param  Collection<int, Wrestler|TagTeam>  $subjects
     * @return array{notices: list<Notice>, totalNotices: int, page: int, pages: int}
     */
    private function notices(EventMatchBuilder $base, array $selection, Carbon $from, Collection $subjects, int $page): array
    {
        $latestPairMatch = (clone $base)->select('events_matches.id')
            ->whereExists(fn (QueryBuilder $pair): QueryBuilder => $pair->selectRaw('1')
                ->from('events_matches_competitors as latest_first')
                ->join('events_matches_competitors as latest_second', 'latest_first.match_id', '=', 'latest_second.match_id')
                ->whereColumn('latest_first.match_id', 'events_matches.id')
                ->whereColumn('latest_first.match_side_id', '<>', 'latest_second.match_side_id')
                ->whereColumn('latest_first.competitor_type', 'first_entry.competitor_type')
                ->whereColumn('latest_first.competitor_id', 'first_entry.competitor_id')
                ->whereColumn('latest_second.competitor_type', 'second_entry.competitor_type')
                ->whereColumn('latest_second.competitor_id', 'second_entry.competitor_id'))
            ->latestEventFirst()->orderBy('events_matches.id')->limit(1);
        $query = $this->sourceRows(clone $base)->where('history.history_date', '>=', $from)
            ->join('events_matches_competitors as first_entry', 'first_entry.match_id', '=', 'history.id')
            ->join('events_matches_competitors as second_entry', 'second_entry.match_id', '=', 'history.id')
            ->whereColumn('first_entry.match_side_id', '<>', 'second_entry.match_side_id')
            ->where(fn (QueryBuilder $ordered): QueryBuilder => $ordered
                ->whereColumn('first_entry.competitor_type', '<', 'second_entry.competitor_type')
                ->orWhere(fn (QueryBuilder $sameType): QueryBuilder => $sameType
                    ->whereColumn('first_entry.competitor_type', 'second_entry.competitor_type')
                    ->whereColumn('first_entry.competitor_id', '<', 'second_entry.competitor_id')));
        $query->where(function (QueryBuilder $opposition) use ($selection): void {
            foreach (array_unique(array_column($selection, 'side')) as $side) {
                $opposition->orWhere(function (QueryBuilder $pair) use ($selection, $side): void {
                    $this->selected($pair, 'first_entry', array_values(array_filter($selection, fn (array $entry): bool => $entry['side'] === $side)));
                    $this->selected($pair, 'second_entry', array_values(array_filter($selection, fn (array $entry): bool => $entry['side'] !== $side)));
                });
            }
        })->select('first_entry.competitor_type as first_type', 'first_entry.competitor_id as first_id',
            'second_entry.competitor_type as second_type', 'second_entry.competitor_id as second_id')
            ->selectRaw('MAX(history.history_date) AS latest_date')
            ->selectSub($latestPairMatch, 'latest_match_id')
            ->selectRaw('COUNT(CASE WHEN history.history_recorded = 1 THEN 1 END) AS recorded')
            ->selectRaw('COUNT(CASE WHEN history.history_recorded = 0 THEN 1 END) AS incomplete')
            ->groupBy('first_entry.competitor_type', 'first_entry.competitor_id', 'second_entry.competitor_type', 'second_entry.competitor_id');
        $total = DB::query()->fromSub(clone $query, 'notice_pairs')->count();
        $pages = max(1, (int) ceil($total / self::PREVIEW_ROWS));
        $page = min($page, $pages);
        $rows = $query->orderByDesc('latest_date')->orderBy('first_type')->orderBy('first_id')->orderBy('second_type')->orderBy('second_id')
            ->offset(($page - 1) * self::PREVIEW_ROWS)->limit(self::PREVIEW_ROWS)->get();
        $sourceMatches = (clone $base)->whereKey($rows->pluck('latest_match_id')->all())
            ->withRecordedResultFlag()->with('event.promotion')->get();

        return [
            'notices' => array_values($rows->map(function (stdClass $row) use ($subjects, $sourceMatches): array {
                $first = $subjects->sole(fn (Wrestler|TagTeam $subject): bool => $this->key($subject) === $row->first_type.':'.$row->first_id);
                $second = $subjects->sole(fn (Wrestler|TagTeam $subject): bool => $this->key($subject) === $row->second_type.':'.$row->second_id);

                $source = $sourceMatches->sole('id', (int) $row->latest_match_id);
                $rawFinish = $source->getRawOriginal('match_finish');
                $finish = is_string($rawFinish) ? MatchFinish::tryFrom($rawFinish) : null;

                return ['first' => $first->name, 'second' => $second->name,
                    'event' => $source->event->name, 'number' => $source->match_number, 'sourceUrl' => route('events.show', $source->event),
                    'type' => $source->match_type->label(),
                    'finish' => $source->getAttribute('history_recorded') && $finish !== null ? $finish->label() : __($rawFinish === null ? 'matches.history.result_missing' : 'matches.history.result_incomplete'), 'url' => $this->opponentUrl($first, $second),
                    'recorded' => (int) $row->recorded, 'incomplete' => (int) $row->incomplete,
                    'latestDate' => Carbon::parse($row->latest_date)->setTimezone(Promotion::zoneOf($subjects->firstOrFail()->promotion))->toDateString()];
            })->all()),
            'totalNotices' => $total, 'page' => $page, 'pages' => $pages,
        ];
    }

    /** @param list<array{type: string, id: int, side: int}> $selection */
    private function selected(QueryBuilder $query, string $alias, array $selection): void
    {
        $query->where(function (QueryBuilder $query) use ($alias, $selection): void {
            foreach (array_unique(array_column($selection, 'type')) as $type) {
                $ids = array_column(array_filter($selection, fn (array $entry): bool => $entry['type'] === $type), 'id');
                $query->orWhere(fn (QueryBuilder $kind): QueryBuilder => $kind->where("{$alias}.competitor_type", $type)->whereIn("{$alias}.competitor_id", $ids));
            }
        });
    }

    private function directType(MatchType $type, string $alias): bool
    {
        return $alias === (new Wrestler)->getMorphClass()
            ? $type === MatchType::Singles
            : in_array($type, [MatchType::TagTeam, MatchType::TornadoTagTeam, MatchType::SixManTagTeam, MatchType::EightManTagTeam, MatchType::TenManTagTeam], true);
    }

    /** @return HistoryRow */
    private function row(EventMatch $match): array
    {
        $rawFinish = $match->getRawOriginal('match_finish');
        $finish = is_string($rawFinish) ? MatchFinish::tryFrom($rawFinish) : null;
        $winner = $match->competitors->firstWhere('match_side_id', $match->winning_side_id);

        return [
            'event' => $match->event->name, 'date' => Promotion::toLocalTime($match->event->promotion, Carbon::parse($match->event->date))->toDateString(),
            'url' => route('events.show', $match->event), 'number' => $match->match_number, 'type' => $match->match_type->label(),
            'finish' => $finish?->label() ?? __('matches.history.result_missing'),
            'winnerSide' => $winner?->side->position,
            'recorded' => (bool) $match->getAttribute('history_recorded'),
            'sides' => array_values($match->competitors->map(fn (MatchCompetitor $entry): array => $this->sideRow($entry, $match->event->promotion_id))->all()),
        ];
    }

    /** @return array{name: string, side: int, deleted: bool} */
    private function sideRow(MatchCompetitor $entry, ?int $promotionId): array
    {
        $competitor = $entry->getRelation('competitor');

        if ((! $competitor instanceof Wrestler && ! $competitor instanceof TagTeam) || $competitor->promotion_id !== $promotionId) {
            return ['name' => __('matches.history.record_unavailable'), 'side' => $entry->side->position, 'deleted' => false];
        }

        return ['name' => $competitor->name, 'side' => $entry->side->position, 'deleted' => $competitor->trashed()];
    }

    private function key(Wrestler|TagTeam $subject): string
    {
        return $subject->getMorphClass().':'.$subject->id;
    }

    private function profileUrl(Wrestler|TagTeam $subject): ?string
    {
        return $subject->trashed() ? null : route($subject instanceof Wrestler ? 'wrestlers.show' : 'tag-teams.show', $subject);
    }

    private function opponentUrl(Wrestler|TagTeam $subject, Wrestler|TagTeam $opponent): ?string
    {
        if ($subject->trashed() && $opponent->trashed()) {
            return null;
        }

        if ($subject->trashed()) {
            return route($opponent instanceof Wrestler ? 'wrestlers.show' : 'tag-teams.show', [$opponent, 'opponent' => $this->key($subject)]);
        }

        return route($subject instanceof Wrestler ? 'wrestlers.show' : 'tag-teams.show', [$subject, 'opponent' => $this->key($opponent)]);
    }
}
