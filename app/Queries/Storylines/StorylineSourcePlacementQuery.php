<?php

declare(strict_types=1);

namespace App\Queries\Storylines;

use App\Models\Events\Event;

/** Whether the event still has the match a segment is placed after. */
class StorylineSourcePlacementQuery
{
    public function get(Event $event, int $matchId): bool
    {
        return $event->matches()->whereKey($matchId)->exists();
    }
}
