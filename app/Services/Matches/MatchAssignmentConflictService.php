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
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class MatchAssignmentConflictService
{
    public function __construct(
        private readonly MatchCompetitorConflictService $competitorConflicts,
        private readonly MatchRefereeConflictService $refereeConflicts,
        private readonly MatchTitleConflictService $titleConflicts,
    ) {}

    public function ensureEventCanBeRescheduled(Event $event, ?Carbon $date): void
    {
        if ($date === null) {
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
     * @return Collection<int, int>
     */
    public function lockConflictingEventIds(EventMatch $eventMatch): Collection
    {
        $event = $eventMatch->event()->firstOrFail();

        return Event::query()
            ->where(function (Builder $query) use ($event): void {
                $query->whereKey($event->id);

                if ($event->date !== null) {
                    $query->orWhere('date', $event->date);
                }
            })
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id'])
            ->map(fn (Event $conflictingEvent): int => $conflictingEvent->id);
    }
}
