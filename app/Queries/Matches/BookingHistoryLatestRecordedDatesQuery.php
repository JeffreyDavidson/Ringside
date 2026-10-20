<?php

declare(strict_types=1);

namespace App\Queries\Matches;

use App\Builders\Matches\EventMatchBuilder;
use App\Models\Matches\EventMatch;
use App\ValueObjects\CompetitorSelection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use stdClass;

/** Reads the latest earlier recorded match date (UTC) of each selected competitor. */
final class BookingHistoryLatestRecordedDatesQuery
{
    /**
     * @return Collection<int, stdClass> rows of `competitor_type`, `competitor_id` and `latest_date`
     */
    public function get(int $promotionId, Carbon $reference, ?int $excludedMatchId, CompetitorSelection $selection): Collection
    {
        $query = EventMatch::query()
            ->forPromotionHistory($promotionId, $reference, $excludedMatchId)
            ->asHistorySource()
            ->join('events_matches_competitors as participant', 'participant.match_id', '=', 'history.id');
        EventMatchBuilder::whereSelectedCompetitors($query, 'participant', $selection);

        return $query->where('history.history_recorded', 1)
            ->select('participant.competitor_type', 'participant.competitor_id')
            ->selectRaw('MAX(history.history_date) AS latest_date')
            ->groupBy('participant.competitor_type', 'participant.competitor_id')
            ->get();
    }
}
