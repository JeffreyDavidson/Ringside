<?php

declare(strict_types=1);

namespace App\Lifecycle\Matches;

use App\Lifecycle\Tournaments\TournamentLocks;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Tournaments\TournamentMatchLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

final readonly class MatchSchedulingLock
{
    public function __construct(private TournamentLocks $tournamentLocks) {}

    /**
     * Lock the complete scheduling event set for an event: the event itself plus every other event on
     * the same exact date and time, in a single statement ordered by ascending id. An unscheduled event
     * conflicts only within its own card, so its set is the event alone.
     *
     * This is the first lock every scheduling operation must take, before any match or resource row,
     * so concurrent bookings on different events at the same time always queue in the same order
     * instead of deadlocking. The date is read before it is locked, so it is verified against the
     * locked row and the set is locked again if the event was rescheduled in between. The second pass
     * always settles because the first pass already holds the event's own row lock.
     *
     * @return Collection<int, int> The locked event ids in ascending order
     *
     * @throws ModelNotFoundException When the event does not exist or is soft deleted
     */
    public function lockEventSet(int $eventId): Collection
    {
        do {
            $date = Event::query()->findOrFail($eventId, ['id', 'date'])->date;
            $lockedEvents = Event::query()
                ->where(function (Builder $query) use ($eventId, $date): void {
                    $query->whereKey($eventId);

                    if ($date !== null) {
                        $query->orWhere('date', $date);
                    }
                })
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'date']);
            $lockedDate = $lockedEvents->firstWhere('id', $eventId)?->date;
        } while ($lockedDate?->getTimestamp() !== $date?->getTimestamp());

        return $lockedEvents->map(fn (Event $lockedEvent): int => $lockedEvent->id);
    }

    /**
     * Lock a match after the scheduling event set of the event it belongs to.
     *
     * The match's event is read without a lock, its event set is locked, and only then is the match row
     * locked and re-verified to still belong to that event. If it moved, the set of the new event is
     * locked instead; the second pass always settles because the match row is then already locked.
     */
    public function lockMatchWithEventSet(EventMatch $eventMatch): EventMatch
    {
        $link = TournamentMatchLink::query()->where('match_id', $eventMatch->id)->first();
        if ($link !== null) {
            $this->tournamentLocks->lock($link->tournament_id);
        }
        do {
            $eventId = EventMatch::query()->withTrashed()->whereKey($eventMatch->getKey())->firstOrFail(['id', 'event_id'])->event_id;
            $this->lockEventSet($eventId);
            $lockedMatch = $eventMatch->refreshForUpdate();
        } while ($lockedMatch->event_id !== $eventId);

        return $lockedMatch;
    }

    /**
     * Lock the scheduling event set of an already locked match's event again, in the same order.
     *
     * @return Collection<int, int>
     */
    public function lockConflictingEventIds(EventMatch $eventMatch): Collection
    {
        return $this->lockEventSet($eventMatch->event_id);
    }
}
