<?php

declare(strict_types=1);

namespace App\Queries\Matches;

use App\Builders\Matches\EventMatchBuilder;
use App\Models\Events\Event;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;

/**
 * Loads up to `$limit` of the most recent earlier matches of subjects of one kind (all wrestlers or all tag teams) in one eager load.
 * Eager loading applies the per-parent limit, including for polymorphic many-to-many relationships.
 */
final class BookingHistoryRecentMatchesQuery
{
    /**
     * @param  Collection<int, covariant Wrestler|TagTeam>  $subjects
     * @return Collection<int, covariant Wrestler|TagTeam> the same subjects, each with its `matches` relation loaded
     */
    public function get(Collection $subjects, int $promotionId, Carbon $reference, ?int $excludedMatchId, int $limit): Collection
    {
        return $subjects->load(['matches' => function (Relation $matches) use ($promotionId, $reference, $excludedMatchId, $limit): void {
            EventMatchBuilder::constrainToPromotionHistory($matches->getQuery(), $promotionId, $reference, $excludedMatchId);
            EventMatchBuilder::addRecordedResultFlag($matches->getQuery());
            $matches->orderByDesc(Event::query()->select('date')->whereColumn('events.id', 'events_matches.event_id'))
                ->orderByDesc('events_matches.event_id')->orderBy('events_matches.match_number')->orderBy('events_matches.id')
                ->with(['event.promotion', 'competitors' => function (Relation $entries): void {
                    $entries->orderBy('events_matches_competitors.id');
                }, 'competitors.side', 'competitors.competitor'])->limit($limit);
        }]);
    }
}
