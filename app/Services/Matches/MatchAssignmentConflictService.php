<?php

declare(strict_types=1);

namespace App\Services\Matches;

use App\Collections\MatchCompetitorsCollection;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final readonly class MatchAssignmentConflictService
{
    public function __construct(
        private MatchCompetitorConflictService $competitorConflicts,
        private MatchRefereeConflictService $refereeConflicts,
        private MatchTitleConflictService $titleConflicts,
    ) {}

    public function ensureEventCanBeRescheduled(Event $event, ?Carbon $date): void
    {
        if (! $date instanceof Carbon) {
            return;
        }

        $conflictingEventIds = Event::query()
            ->where('date', $date)
            ->whereKeyNot($event->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id'])
            ->map(fn (Event $conflictingEvent): int => $conflictingEvent->id);

        if ($conflictingEventIds->isEmpty()) {
            return;
        }

        $matches = EventMatch::query()
            ->whereBelongsTo($event)
            ->with(['competitors.competitor', 'referees', 'titles'])
            ->get();
        $competitors = new MatchCompetitorsCollection(
            $matches
                ->flatMap(fn (EventMatch $match): array => $match->competitors->all())
                ->all(),
        );
        $wrestlers = $competitors->wrestlers();
        $tagTeams = $competitors->tagTeams();
        $referees = $matches->flatMap->referees->unique('id')->values();
        $titles = $matches->flatMap->titles->unique('id')->values();

        if ($wrestlers->isNotEmpty()) {
            $this->ensureWrestlersCanBeAssigned($conflictingEventIds, $wrestlers);
        }

        if ($tagTeams->isNotEmpty()) {
            $this->ensureTagTeamsCanBeAssigned($conflictingEventIds, $tagTeams);
        }

        if ($referees->isNotEmpty()) {
            $this->ensureRefereesCanBeAssigned($event->id, $conflictingEventIds, $referees);
        }

        if ($titles->isNotEmpty()) {
            $this->ensureTitlesCanBeAssigned($conflictingEventIds, $titles);
        }
    }

    /**
     * @param  Collection<int, int>  $conflictingEventIds
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    public function ensureWrestlersCanBeAssigned(Collection $conflictingEventIds, Collection $wrestlers): void
    {
        $this->competitorConflicts->ensureWrestlersCanBeAssigned($conflictingEventIds, $wrestlers);
    }

    /**
     * @param  Collection<int, int>  $conflictingEventIds
     * @param  Collection<int, TagTeam>  $tagTeams
     */
    public function ensureTagTeamsCanBeAssigned(Collection $conflictingEventIds, Collection $tagTeams): void
    {
        $this->competitorConflicts->ensureTagTeamsCanBeAssigned($conflictingEventIds, $tagTeams);
    }

    /**
     * @param  Collection<int, int>  $conflictingEventIds
     * @param  Collection<int, Referee>  $referees
     */
    public function ensureRefereesCanBeAssigned(int $eventId, Collection $conflictingEventIds, Collection $referees): void
    {
        $this->refereeConflicts->ensureCanBeAssigned($eventId, $conflictingEventIds, $referees);
    }

    /**
     * @param  Collection<int, int>  $conflictingEventIds
     * @param  Collection<int, Title>  $titles
     */
    public function ensureTitlesCanBeAssigned(Collection $conflictingEventIds, Collection $titles): void
    {
        $this->titleConflicts->ensureCanBeAssigned($conflictingEventIds, $titles);
    }

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
