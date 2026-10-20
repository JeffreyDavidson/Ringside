<?php

declare(strict_types=1);

namespace App\Lifecycle\Events;

use App\Builders\Events\EventBuilder;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Models\Events\EventPack;
use App\Models\Matches\EventMatch;
use Illuminate\Database\Eloquent\Collection;

/**
 * Locks an event's card in the one order every workflow that edits it shares: the event row, then all its matches
 * (deleted ones too) in ascending id order, then its pack, then its live-show run.
 *
 * Event pack, production, sponsor instruction, live-show and storyline source writes all read the card and compare a
 * fingerprint of it, so they hold these rows for their whole transaction. Taking them in the same order everywhere
 * keeps two of those writes from deadlocking on each other. Call it inside the transaction the locks should live for.
 */
final class EventCardLock
{
    /**
     * Lock the card of an event that has not been deleted.
     *
     * @return array{event: Event, matches: Collection<int, EventMatch>, pack: ?EventPack, run: ?EventLiveRun}
     */
    public function lock(int $eventId): array
    {
        return $this->lockCard(Event::query()->whereKey($eventId));
    }

    /**
     * Lock the card of an event of the given promotion, deleted or not, for a source that may point at past shows.
     *
     * @return array{event: Event, matches: Collection<int, EventMatch>, pack: ?EventPack, run: ?EventLiveRun}
     */
    public function lockIncludingDeleted(int $eventId, int $promotionId): array
    {
        return $this->lockCard(Event::query()->withTrashed()->where('promotion_id', $promotionId)->whereKey($eventId));
    }

    /**
     * @param  EventBuilder<Event>  $events
     * @return array{event: Event, matches: Collection<int, EventMatch>, pack: ?EventPack, run: ?EventLiveRun}
     */
    private function lockCard(EventBuilder $events): array
    {
        $event = $events->lockForUpdate()->firstOrFail();

        return [
            'event' => $event,
            'matches' => EventMatch::query()->withTrashed()->forEventId($event->id)->inLockOrder()->lockForUpdate()->get(),
            'pack' => EventPack::query()->forEventId($event->id)->lockForUpdate()->first(),
            'run' => EventLiveRun::query()->forEventId($event->id)->lockForUpdate()->first(),
        ];
    }
}
