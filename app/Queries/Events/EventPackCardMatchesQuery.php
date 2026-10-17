<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * The matches of an event's card in card order, with the competitors, current tag team members, referees, titles and
 * stipulation each row lists. Deleted referees and members stay in the result so the card can warn about them.
 */
class EventPackCardMatchesQuery
{
    /** @return Collection<int, EventMatch> */
    public function get(Event $event): Collection
    {
        return EventMatch::query()->forEventId($event->id)->inCardOrder()->withDisplayRelations()
            ->withTagTeamMembers(fn (Relation $wrestlers): Builder => $wrestlers->getQuery()->withoutGlobalScope(SoftDeletingScope::class)->orderBy('wrestlers.id'))
            ->with(['matchStipulation', 'referees' => fn (Relation $referees): Builder => $referees->getQuery()->withoutGlobalScope(SoftDeletingScope::class)->orderBy('referees.id')])
            ->get();
    }
}
