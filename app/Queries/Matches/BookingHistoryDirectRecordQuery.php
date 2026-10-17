<?php

declare(strict_types=1);

namespace App\Queries\Matches;

use App\Enums\Matches\MatchFinish;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use stdClass;

/** Reads the counts of the earlier direct meetings of two competitors, seen from the first. */
final class BookingHistoryDirectRecordQuery
{
    /**
     * @return stdClass one row of `recorded`, `incomplete`, `first_wins`, `second_wins`, `draws` and `neutral`
     */
    public function get(int $promotionId, Carbon $reference, ?int $excludedMatchId, Wrestler|TagTeam $first, Wrestler|TagTeam $second): stdClass
    {
        return EventMatch::query()
            ->forPromotionHistory($promotionId, $reference, $excludedMatchId)
            ->directMeetings($first, $second)
            ->asHistorySource()
            ->join('events_matches_competitors as direct_subject', 'direct_subject.match_id', '=', 'history.id')
            ->where('direct_subject.competitor_type', $first->getMorphClass())
            ->where('direct_subject.competitor_id', $first->id)
            ->selectRaw('COUNT(CASE WHEN history.history_recorded = 1 THEN 1 END) AS recorded')
            ->selectRaw('COUNT(CASE WHEN history.history_recorded = 0 THEN 1 END) AS incomplete')
            ->selectRaw('COUNT(CASE WHEN history.history_recorded = 1 AND history.winning_side_id = direct_subject.match_side_id THEN 1 END) AS first_wins')
            ->selectRaw('COUNT(CASE WHEN history.history_recorded = 1 AND history.winning_side_id <> direct_subject.match_side_id THEN 1 END) AS second_wins')
            ->selectRaw('COUNT(CASE WHEN history.history_recorded = 1 AND history.match_finish = ? THEN 1 END) AS draws', [MatchFinish::TimeLimitDraw->value])
            ->selectRaw('COUNT(CASE WHEN history.history_recorded = 1 AND history.match_finish = ? THEN 1 END) AS neutral', [MatchFinish::NoDecision->value])
            ->sole();
    }
}
