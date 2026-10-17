<?php

declare(strict_types=1);

namespace App\Lifecycle\Matches;

use App\Collections\MatchCompetitorsCollection;
use App\Lifecycle\Tournaments\TournamentMatchRequirements;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Scopes\PromotionContextScope;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final readonly class MatchAssignmentEligibility
{
    public function __construct(
        private MatchCompetitorAssignmentEligibility $competitorConflicts,
        private MatchRefereeAssignmentEligibility $refereeConflicts,
        private MatchTitleAssignmentEligibility $titleConflicts,
        private TournamentMatchRequirements $tournamentRequirements,
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

        // The promotion context scope filters through the soft-deletable event, hiding the matches of an
        // event being restored. The caller has already verified ownership of the event.
        $matches = EventMatch::query()
            ->withoutGlobalScope(PromotionContextScope::class)
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
    public function ensureWrestlersCanBeAssigned(Collection $conflictingEventIds, Collection $wrestlers, ?EventMatch $match = null): void
    {
        $this->competitorConflicts->ensureWrestlersCanBeAssigned($conflictingEventIds, $wrestlers, $this->tournamentRequirements->permittedPriorMatchIds($match));
    }

    /**
     * @param  Collection<int, int>  $conflictingEventIds
     * @param  Collection<int, TagTeam>  $tagTeams
     */
    public function ensureTagTeamsCanBeAssigned(Collection $conflictingEventIds, Collection $tagTeams, ?EventMatch $match = null): void
    {
        $this->competitorConflicts->ensureTagTeamsCanBeAssigned($conflictingEventIds, $tagTeams, $this->tournamentRequirements->permittedPriorMatchIds($match));
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
}
