<?php

declare(strict_types=1);

namespace App\Queries\Storylines;

use App\Models\Events\Event;

/** An event of the promotion, deleted or not, with what a storyline source records about it. Null when the promotion has no such event. */
class StorylineSourceEventQuery
{
    public function get(int $promotionId, int $eventId): ?Event
    {
        return Event::query()->withTrashed()->where('promotion_id', $promotionId)->with(['promotion', 'pack', 'liveRun'])->find($eventId);
    }
}
