<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class MatchAssignmentConflictService
{
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
            ->where('event_id', $event->id)
            ->with(['competitors.competitor', 'referees', 'titles'])
            ->get();
        $competitors = $matches->flatMap->competitors->pluck('competitor');
        $wrestlers = $competitors->filter(fn (mixed $competitor): bool => $competitor instanceof Wrestler)->values();
        $tagTeams = $competitors->filter(fn (mixed $competitor): bool => $competitor instanceof TagTeam)->values();
        $referees = $matches->flatMap->referees->unique('id')->values();
        $titles = $matches->flatMap->titles->unique('id')->values();

        if ($wrestlers->isNotEmpty()) {
            $this->ensureCompetitorsCanBeAssigned($conflictingEventIds, $wrestlers, Wrestler::class, 'Wrestler');
        }

        if ($tagTeams->isNotEmpty()) {
            $this->ensureCompetitorsCanBeAssigned($conflictingEventIds, $tagTeams, TagTeam::class, 'Tag team');
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
     * @param  Collection<int, Wrestler>|Collection<int, TagTeam>  $competitors
     * @param  class-string<Wrestler|TagTeam>  $competitorType
     */
    public function ensureCompetitorsCanBeAssigned(
        Collection $conflictingEventIds,
        Collection $competitors,
        string $competitorType,
        string $entityType,
    ): void {
        $conflictingCompetitor = MatchCompetitor::query()
            ->forCompetitorIds(
                $competitorType,
                $competitors->map(fn (Wrestler|TagTeam $competitor): int => $competitor->id),
            )
            ->forEventIds($conflictingEventIds)
            ->first(['competitor_id']);

        if ($conflictingCompetitor === null) {
            return;
        }

        $conflictingCompetitorId = $conflictingCompetitor->competitor_id;
        $competitor = $competitors->firstWhere('id', $conflictingCompetitorId);

        throw SchedulingConflictException::competitorAlreadyBooked(
            $entityType,
            $competitor === null ? "ID: {$conflictingCompetitorId}" : (string) $competitor->name,
        );
    }

    /**
     * @param  Collection<int, int>  $conflictingEventIds
     * @param  Collection<int, Referee>  $referees
     */
    public function ensureRefereesCanBeAssigned(int $eventId, Collection $conflictingEventIds, Collection $referees): void
    {
        $conflictingEventIds = $conflictingEventIds
            ->reject(fn (int $conflictingEventId): bool => $conflictingEventId === $eventId);

        if ($conflictingEventIds->isEmpty()) {
            return;
        }

        $conflictingReferee = EventMatch::query()
            ->forEventIds($conflictingEventIds)
            ->withAnyRefereeIds($referees->map(fn (Referee $referee): int => $referee->id))
            ->with('referees:id,first_name,last_name,full_name')
            ->get()
            ->flatMap->referees
            ->first(fn (Referee $referee): bool => $referees->contains('id', $referee->id));

        if ($conflictingReferee === null) {
            return;
        }

        throw SchedulingConflictException::refereeAlreadyAssigned($conflictingReferee->full_name);
    }

    /**
     * @param  Collection<int, int>  $conflictingEventIds
     * @param  Collection<int, Title>  $titles
     */
    public function ensureTitlesCanBeAssigned(Collection $conflictingEventIds, Collection $titles): void
    {
        $conflictingTitleId = EventMatch::query()
            ->forEventIds($conflictingEventIds)
            ->withAnyTitleIds($titles->map(fn (Title $title): int => $title->id))
            ->with('titles:id,name')
            ->get()
            ->flatMap->titles
            ->first(fn (Title $title): bool => $titles->contains('id', $title->id))
            ?->id;

        if ($conflictingTitleId === null) {
            return;
        }

        $title = $titles->firstWhere('id', $conflictingTitleId);

        throw SchedulingConflictException::titleAlreadyAssigned($title === null ? "ID: {$conflictingTitleId}" : (string) $title->name);
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
