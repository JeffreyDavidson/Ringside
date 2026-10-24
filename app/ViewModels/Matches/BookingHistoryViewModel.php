<?php

declare(strict_types=1);

namespace App\ViewModels\Matches;

use App\Enums\Matches\MatchFinish;
use App\Enums\Matches\MatchType;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Matches\BookingHistoryDirectRecordQuery;
use App\Queries\Matches\BookingHistoryLatestRecordedDatesQuery;
use App\Queries\Matches\BookingHistoryNoticePairsQuery;
use App\Queries\Matches\BookingHistoryNoticeSourcesQuery;
use App\Queries\Matches\BookingHistoryRecentMatchesQuery;
use App\Queries\Matches\BookingHistorySubjectsQuery;
use App\Services\Promotions\PromotionTimeZoneService;
use App\ValueObjects\CompetitorSelection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use stdClass;

/**
 * Builds the booking history modal: current retained source records, filtered by event time; this is not a
 * reconstruction of past database revisions.
 *
 * @phpstan-type HistoryRow array{date: string, event: string, eventId: int, number: int, type: string, finish: string, recorded: bool, winnerSide: ?int, sides: list<array{name: string, side: int, deleted: bool}>}
 * @phpstan-type Subject array{key: string, type: string, id: int, name: string, deleted: bool, latestRecordedDate: ?string, recent: list<HistoryRow>}
 * @phpstan-type Pair array{type: string, id: int, deleted: bool}
 * @phpstan-type DirectRecord array{first: string, second: string, pair: array{first: Pair, second: Pair}, firstWins: int, secondWins: int, draws: int, neutral: int, incomplete: int, recorded: int}
 * @phpstan-type Notice array{first: string, second: string, pair: array{first: Pair, second: Pair}, recorded: int, incomplete: int, latestDate: string, event: string, eventId: int, number: int, type: string, finish: string}
 * @phpstan-type Snapshot array{available: bool, reference: string, from: string, subjects: list<Subject>, direct: ?DirectRecord, notices: list<Notice>, totalNotices: int, page: int, pages: int}
 */
class BookingHistoryViewModel
{
    public const int RECENT_DAYS = 90;

    public const int PREVIEW_ROWS = 5;

    public function __construct(
        private readonly BookingHistorySubjectsQuery $subjectsQuery,
        private readonly BookingHistoryRecentMatchesQuery $recentMatchesQuery,
        private readonly BookingHistoryLatestRecordedDatesQuery $latestRecordedDatesQuery,
        private readonly BookingHistoryDirectRecordQuery $directRecordQuery,
        private readonly BookingHistoryNoticePairsQuery $noticePairsQuery,
        private readonly BookingHistoryNoticeSourcesQuery $noticeSourcesQuery,
    ) {}

    /**
     * The selection must already be validated (see BookingHistorySelection): every side has competitors the match type allows,
     * and each belongs to the event's promotion.
     *
     * @return Snapshot
     */
    public function snapshot(Event $event, MatchType $type, CompetitorSelection $selection, ?int $excludedMatchId = null, int $page = 1): array
    {
        $event->loadMissing('promotion');
        $checkedAt = now();
        $reference = $event->date instanceof Carbon && $event->date->lessThan($checkedAt) ? $event->date->copy() : $checkedAt;
        $from = $reference->copy()->setTimezone(PromotionTimeZoneService::zoneOf($event->promotion))
            ->subDays(self::RECENT_DAYS)->setTimezone('UTC');
        $snapshot = [
            'available' => false, 'reference' => $reference->toIso8601String(), 'from' => $from->toIso8601String(),
            'subjects' => [], 'direct' => null, 'notices' => [], 'totalNotices' => 0, 'page' => 1, 'pages' => 1,
        ];

        if ($event->promotion_id === null) {
            return $snapshot;
        }

        $subjects = $this->subjects($selection, $event->promotion_id);
        $latest = $this->latestRecordedDatesQuery->get($event->promotion_id, $reference, $excludedMatchId, $selection)
            ->mapWithKeys(fn (stdClass $row): array => [$row->competitor_type.':'.$row->competitor_id => (string) $row->latest_date])
            ->all();

        $withMatches = $subjects->groupBy(fn (Wrestler|TagTeam $subject): string => $subject->getMorphClass())
            ->flatMap(fn (Collection $group): Collection => $this->recentMatchesQuery->get(new Collection($group->all()), $event->promotion_id, $reference, $excludedMatchId, self::PREVIEW_ROWS))
            ->keyBy(fn (Wrestler|TagTeam $loaded): string => $this->key($loaded))
            ->all();

        $snapshot['available'] = true;
        $snapshot['subjects'] = array_values($subjects->map(fn (Wrestler|TagTeam $subject): array => [
            'key' => $this->key($subject), 'type' => $subject->getMorphClass(), 'id' => $subject->id, 'name' => $subject->name, 'deleted' => $subject->trashed(),
            'latestRecordedDate' => isset($latest[$this->key($subject)])
                ? Carbon::parse($latest[$this->key($subject)])->setTimezone(PromotionTimeZoneService::zoneOf($event->promotion))->toDateString()
                : null,
            'recent' => array_values($withMatches[$this->key($subject)]->matches->map(fn (EventMatch $match): array => $this->row($match))->all()),
        ])->all());

        if ($selection->isHeadToHead()) {
            [$firstEntry, $secondEntry] = $selection->entries();

            if ($this->directType($type, $firstEntry['type'])) {
                $snapshot['direct'] = $this->directRecord(
                    $event->promotion_id,
                    $reference,
                    $excludedMatchId,
                    $subjects->firstOrFail(),
                    $subjects->sole(fn (Wrestler|TagTeam $subject): bool => $this->key($subject) === $secondEntry['type'].':'.$secondEntry['id']),
                );
            }
        }

        $notices = $this->notices($event->promotion_id, $reference, $excludedMatchId, $selection, $from, $subjects, max(1, $page));

        return [...$snapshot, ...$notices];
    }

    /**
     * @return Collection<int, Wrestler|TagTeam>
     */
    private function subjects(CompetitorSelection $selection, int $promotionId): Collection
    {
        $ids = $selection->idsByType();
        $wrestlers = $this->subjectsQuery->get(new Wrestler, $ids[(new Wrestler)->getMorphClass()] ?? [], $promotionId);
        $teams = $this->subjectsQuery->get(new TagTeam, $ids[(new TagTeam)->getMorphClass()] ?? [], $promotionId);
        // Eloquent merge keys by numeric id, which would collapse a wrestler and team with the same id.
        $keyed = collect([...$wrestlers, ...$teams])->keyBy(fn (Wrestler|TagTeam $subject): string => $this->key($subject));

        return new Collection(array_map(
            fn (array $entry): Wrestler|TagTeam => $keyed->firstOrFail(fn (Wrestler|TagTeam $subject, string $key): bool => $key === $entry['type'].':'.$entry['id']),
            $selection->entries(),
        ));
    }

    /** @return DirectRecord */
    private function directRecord(int $promotionId, Carbon $reference, ?int $excludedMatchId, Wrestler|TagTeam $first, Wrestler|TagTeam $second): array
    {
        $row = $this->directRecordQuery->get($promotionId, $reference, $excludedMatchId, $first, $second);

        return [
            'first' => $first->name, 'second' => $second->name, 'pair' => $this->pair($first, $second),
            'recorded' => (int) $row->recorded, 'incomplete' => (int) $row->incomplete,
            'firstWins' => (int) $row->first_wins, 'secondWins' => (int) $row->second_wins,
            'draws' => (int) $row->draws, 'neutral' => (int) $row->neutral,
        ];
    }

    /**
     * @param  Collection<int, Wrestler|TagTeam>  $subjects
     * @return array{notices: list<Notice>, totalNotices: int, page: int, pages: int}
     */
    private function notices(int $promotionId, Carbon $reference, ?int $excludedMatchId, CompetitorSelection $selection, Carbon $from, Collection $subjects, int $page): array
    {
        $pairs = $this->noticePairsQuery->get($promotionId, $reference, $excludedMatchId, $selection, $from);
        $total = $pairs->getCountForPagination();
        $pages = max(1, (int) ceil($total / self::PREVIEW_ROWS));
        $page = min($page, $pages);
        $rows = $pairs->forPage($page, self::PREVIEW_ROWS)->get();
        $sourceMatches = $this->noticeSourcesQuery->get($promotionId, $reference, $excludedMatchId, $rows->map(fn (stdClass $row): int => (int) $row->latest_match_id)->all());

        return [
            'notices' => array_values($rows->map(function (stdClass $row) use ($subjects, $sourceMatches): array {
                $first = $subjects->sole(fn (Wrestler|TagTeam $subject): bool => $this->key($subject) === $row->first_type.':'.$row->first_id);
                $second = $subjects->sole(fn (Wrestler|TagTeam $subject): bool => $this->key($subject) === $row->second_type.':'.$row->second_id);

                $source = $sourceMatches->sole('id', (int) $row->latest_match_id);
                $rawFinish = $source->getRawOriginal('match_finish');
                $finish = is_string($rawFinish) ? MatchFinish::tryFrom($rawFinish) : null;

                return ['first' => $first->name, 'second' => $second->name,
                    'event' => $source->event->name, 'eventId' => $source->event->id, 'number' => $source->match_number,
                    'type' => $source->match_type->label(),
                    'finish' => $source->getAttribute('history_recorded') && $finish !== null ? $finish->label() : __($rawFinish === null ? 'matches.history.result_missing' : 'matches.history.result_incomplete'), 'pair' => $this->pair($first, $second),
                    'recorded' => (int) $row->recorded, 'incomplete' => (int) $row->incomplete,
                    'latestDate' => Carbon::parse($row->latest_date)->setTimezone(PromotionTimeZoneService::zoneOf($subjects->firstOrFail()->promotion))->toDateString()];
            })->all()),
            'totalNotices' => $total, 'page' => $page, 'pages' => $pages,
        ];
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
            'event' => $match->event->name, 'date' => PromotionTimeZoneService::toLocalTime($match->event->promotion, Carbon::parse($match->event->date))->toDateString(),
            'eventId' => $match->event->id, 'number' => $match->match_number, 'type' => $match->match_type->label(),
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

    /** @return array{first: Pair, second: Pair} */
    private function pair(Wrestler|TagTeam $first, Wrestler|TagTeam $second): array
    {
        return [
            'first' => ['type' => $first->getMorphClass(), 'id' => $first->id, 'deleted' => $first->trashed()],
            'second' => ['type' => $second->getMorphClass(), 'id' => $second->id, 'deleted' => $second->trashed()],
        ];
    }
}
