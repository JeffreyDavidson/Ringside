<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Builders\Matches\EventMatchBuilder;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Scopes\PromotionContextScope;
use Illuminate\Database\Eloquent\Collection;

/** The matches on an event's card with everyone booked on them: competitors, current tag team members and referees. */
class EventRosterQuery
{
    /**
     * @param  bool  $excludePastResults  leave out matches that already have a result when the event date has passed
     * @return Collection<int, EventMatch>
     */
    public function get(Event $event, bool $excludePastResults = false): Collection
    {
        if (! $event->exists) {
            return new Collection;
        }

        // A restored event is still soft-deleted here. The caller owns this exact event; never search another promotion.
        return EventMatch::query()->withoutGlobalScope(PromotionContextScope::class)->forEventId($event->id)
            ->when($excludePastResults && $event->date?->isPast(), fn (EventMatchBuilder $query): EventMatchBuilder => $query->whereNull('match_finish'))
            ->withDisplayRelations()->withTagTeamMembers()->get();
    }
}
