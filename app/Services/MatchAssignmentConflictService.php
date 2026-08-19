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
use Illuminate\Support\Collection;

final class MatchAssignmentConflictService
{
    /**
     * @param  Collection<int, int>  $conflictingEventIds
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    public function ensureWrestlersCanBeAssigned(Collection $conflictingEventIds, Collection $wrestlers): void
    {
        $conflictingCompetitor = MatchCompetitor::query()
            ->forCompetitorIds(
                Wrestler::class,
                $wrestlers->map(fn (Wrestler $wrestler): int => $wrestler->id),
            )
            ->forEventIds($conflictingEventIds)
            ->first(['competitor_id']);

        if ($conflictingCompetitor === null) {
            return;
        }

        $conflictingWrestlerId = $conflictingCompetitor->competitor_id;
        $wrestler = $wrestlers->firstWhere('id', $conflictingWrestlerId);

        throw SchedulingConflictException::competitorAlreadyBooked(
            'Wrestler',
            $wrestler === null ? "ID: {$conflictingWrestlerId}" : (string) $wrestler->name,
        );
    }

    /**
     * @param  Collection<int, int>  $conflictingEventIds
     * @param  Collection<int, TagTeam>  $tagTeams
     */
    public function ensureTagTeamsCanBeAssigned(Collection $conflictingEventIds, Collection $tagTeams): void
    {
        $conflictingCompetitor = MatchCompetitor::query()
            ->forCompetitorIds(
                TagTeam::class,
                $tagTeams->map(fn (TagTeam $tagTeam): int => $tagTeam->id),
            )
            ->forEventIds($conflictingEventIds)
            ->first(['competitor_id']);

        if ($conflictingCompetitor === null) {
            return;
        }

        $conflictingTagTeamId = $conflictingCompetitor->competitor_id;
        $tagTeam = $tagTeams->firstWhere('id', $conflictingTagTeamId);

        throw SchedulingConflictException::competitorAlreadyBooked(
            'Tag team',
            $tagTeam === null ? "ID: {$conflictingTagTeamId}" : (string) $tagTeam->name,
        );
    }

    /**
     * @param  Collection<int, int>  $conflictingEventIds
     * @param  Collection<int, Referee>  $referees
     */
    public function ensureRefereesCanBeAssigned(EventMatch $eventMatch, Collection $conflictingEventIds, Collection $referees): void
    {
        $conflictingEventIds = $conflictingEventIds
            ->reject(fn (int $eventId): bool => $eventId === $eventMatch->event_id);

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
