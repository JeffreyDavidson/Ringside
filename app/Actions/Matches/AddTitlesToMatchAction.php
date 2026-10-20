<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Lifecycle\Matches\MatchAssignmentEligibility;
use App\Lifecycle\Matches\MatchConfigurationRequirements;
use App\Lifecycle\Matches\MatchSchedulingLock;
use App\Lifecycle\Matches\MatchTitleRequirements;
use App\Lifecycle\Tournaments\TournamentMatchRequirements;
use App\Models\Matches\EventMatch;
use App\Models\Titles\Title;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AddTitlesToMatchAction
{
    public function __construct(
        private readonly MatchSchedulingLock $schedulingLock,
        private readonly MatchAssignmentEligibility $assignmentEligibility,
        private readonly MatchTitleRequirements $requirements,
        private readonly MatchConfigurationRequirements $configurationRequirements,
        private readonly TournamentMatchRequirements $tournamentRequirements,
    ) {}

    /**
     * Add titles to an event match.
     *
     * This handles the complete championship stakes assignment workflow:
     * - Validates titles are active and available for championship competition
     * - Associates titles with the match to indicate championship stakes
     * - Creates title match records for each championship at stake
     * - Establishes the match as a title defense, title match, or unification bout
     * - Enables championship tracking, title changes, and reign continuity
     * - Validates current champions are participating in the title match
     *
     * BUSINESS RULES:
     * - Titles must be active on the event's date (active now for an unscheduled event)
     * - Championship matches require the champion on the event's date to be competing
     * - Titles cannot be defended in multiple matches on the same event
     * - Title matches must have proper championship match designation
     * - Vacant titles can be competed for in tournament or special matches
     *
     * BUSINESS IMPACT:
     * - Creates high-stakes championship competition for increased fan interest
     * - Enables proper title reign tracking and championship history
     * - Supports championship-based storylines and promotional marketing
     * - Affects wrestler rankings and championship contender status
     * - Drives revenue through championship match premium pricing
     *
     * @param  EventMatch  $eventMatch  The match to add titles to
     * @param  Collection<int, Title>  $titles  The championships at stake in the match
     */
    public function handle(EventMatch $eventMatch, Collection $titles): void
    {
        $requestedTitles = $titles->unique('id')->values();

        if ($requestedTitles->isEmpty()) {
            throw EntityNotAvailableException::forMatchAssignment('titles');
        }

        DB::transaction(function () use ($eventMatch, $requestedTitles): void {
            $lockedMatch = $this->schedulingLock->lockMatchWithEventSet($eventMatch);
            $this->configurationRequirements->ensureParticipantsWithinEventPromotion(
                $lockedMatch->event()->firstOrFail(),
                'titles',
                $requestedTitles->all(),
            );
            $this->handleWithinTransaction($lockedMatch, $requestedTitles);
        }, attempts: 3);
    }

    /**
     * Assign titles while the caller owns the match transaction and lock.
     *
     * @param  Collection<int, Title>  $titles
     */
    public function handleWithinTransaction(EventMatch $lockedMatch, Collection $titles): void
    {
        $this->tournamentRequirements->ensureFinalStakes($lockedMatch);
        $requestedTitles = $titles->unique('id')->values();

        if ($requestedTitles->isEmpty()) {
            throw EntityNotAvailableException::forMatchAssignment('titles');
        }

        $conflictingEventIds = $this->schedulingLock->lockConflictingEventIds($lockedMatch);
        $lockedTitles = Title::query()
            ->whereKey($requestedTitles->pluck('id'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $eventDate = $lockedMatch->event()->firstOrFail()->date?->copy()->utc();
        $activeTitleCount = Title::query()
            ->whereKey($lockedTitles->modelKeys())
            ->activeOn($eventDate)
            ->count();

        if ($lockedTitles->count() !== $requestedTitles->count() || $activeTitleCount !== $lockedTitles->count()) {
            throw EntityNotAvailableException::forMatchAssignment('titles');
        }

        $this->assignmentEligibility->ensureTitlesCanBeAssigned($conflictingEventIds, $lockedTitles);
        $this->requirements->ensureSatisfied($lockedMatch, $lockedTitles);

        $lockedTitles->each(function (Title $title) use ($lockedMatch): void {
            $lockedMatch->titles()->attach($title->id);
        });
    }
}
