<?php

declare(strict_types=1);

namespace App\Queries\Matches;

use App\Models\Matches\EventMatch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/** Reads the earlier matches a notice links to, with their recorded-result flag and event. */
final class BookingHistoryNoticeSourcesQuery
{
    /**
     * @param  array<int, int|string>  $matchIds
     * @return Collection<int, EventMatch>
     */
    public function get(int $promotionId, Carbon $reference, ?int $excludedMatchId, array $matchIds): Collection
    {
        return EventMatch::query()
            ->forPromotionHistory($promotionId, $reference, $excludedMatchId)
            ->whereKey($matchIds)
            ->withRecordedResultFlag()
            ->with('event.promotion')
            ->get();
    }
}
