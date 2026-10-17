<?php

declare(strict_types=1);

namespace App\Queries\Matches;

use App\Builders\Matches\EventMatchBuilder;
use App\Models\Matches\EventMatch;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;

/**
 * Prepares the read of opposing pairs, one row per pair, that met since `$from`: the recorded and incomplete meeting
 * counts, the latest date and the id of the latest dated meeting. The caller counts and pages it.
 */
final class BookingHistoryNoticePairsQuery
{
    /**
     * @param  list<array{type: string, id: int, side: int}>  $selection
     */
    public function get(int $promotionId, Carbon $reference, ?int $excludedMatchId, array $selection, Carbon $from): QueryBuilder
    {
        $base = EventMatch::query()->forPromotionHistory($promotionId, $reference, $excludedMatchId);
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

        return $base->asHistorySource()->where('history.history_date', '>=', $from)
            ->join('events_matches_competitors as first_entry', 'first_entry.match_id', '=', 'history.id')
            ->join('events_matches_competitors as second_entry', 'second_entry.match_id', '=', 'history.id')
            ->whereColumn('first_entry.match_side_id', '<>', 'second_entry.match_side_id')
            ->where(fn (QueryBuilder $ordered): QueryBuilder => $ordered
                ->whereColumn('first_entry.competitor_type', '<', 'second_entry.competitor_type')
                ->orWhere(fn (QueryBuilder $sameType): QueryBuilder => $sameType
                    ->whereColumn('first_entry.competitor_type', 'second_entry.competitor_type')
                    ->whereColumn('first_entry.competitor_id', '<', 'second_entry.competitor_id')))
            ->where(function (QueryBuilder $opposition) use ($selection): void {
                foreach (array_unique(array_column($selection, 'side')) as $side) {
                    $opposition->orWhere(function (QueryBuilder $pair) use ($selection, $side): void {
                        EventMatchBuilder::whereSelectedCompetitors($pair, 'first_entry', array_values(array_filter($selection, fn (array $entry): bool => $entry['side'] === $side)));
                        EventMatchBuilder::whereSelectedCompetitors($pair, 'second_entry', array_values(array_filter($selection, fn (array $entry): bool => $entry['side'] !== $side)));
                    });
                }
            })
            ->select('first_entry.competitor_type as first_type', 'first_entry.competitor_id as first_id',
                'second_entry.competitor_type as second_type', 'second_entry.competitor_id as second_id')
            ->selectRaw('MAX(history.history_date) AS latest_date')
            ->selectSub($latestPairMatch, 'latest_match_id')
            ->selectRaw('COUNT(CASE WHEN history.history_recorded = 1 THEN 1 END) AS recorded')
            ->selectRaw('COUNT(CASE WHEN history.history_recorded = 0 THEN 1 END) AS incomplete')
            ->groupBy('first_entry.competitor_type', 'first_entry.competitor_id', 'second_entry.competitor_type', 'second_entry.competitor_id')
            ->orderByDesc('latest_date')
            ->orderBy('first_type')->orderBy('first_id')->orderBy('second_type')->orderBy('second_id');
    }
}
