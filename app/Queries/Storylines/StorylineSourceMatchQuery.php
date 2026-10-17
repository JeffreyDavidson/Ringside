<?php

declare(strict_types=1);

namespace App\Queries\Storylines;

use App\Models\Matches\EventMatch;

/** A match of the event, deleted or not, with its competitors and titles. Null when the event has no such match. */
class StorylineSourceMatchQuery
{
    public function get(int $eventId, int $matchId): ?EventMatch
    {
        return EventMatch::query()->withTrashed()->where('event_id', $eventId)->with(['competitors', 'titles'])->find($matchId);
    }
}
