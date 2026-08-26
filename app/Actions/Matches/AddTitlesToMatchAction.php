<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Lifecycle\MatchTitleRequirements;
use App\Models\Matches\EventMatch;
use App\Models\Titles\Title;
use App\Services\EventMatchAssignmentService;
use App\Services\MatchAssignmentConflictService;
use Illuminate\Support\Collection;

class AddTitlesToMatchAction
{
    public function __construct(
        private readonly MatchAssignmentConflictService $conflictService,
        private readonly MatchTitleRequirements $requirements,
        private readonly EventMatchAssignmentService $assignmentTransaction,
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
     * - Titles must be active and not retired or suspended
     * - Championship matches require current title holders to be competing
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

        $this->assignmentTransaction->execute($eventMatch, function (EventMatch $lockedMatch) use ($requestedTitles): void {
            $this->handleWithinTransaction($lockedMatch, $requestedTitles);
        });
    }

    /**
     * Assign titles while the caller owns the match transaction and lock.
     *
     * @param  Collection<int, Title>  $titles
     */
    public function handleWithinTransaction(EventMatch $lockedMatch, Collection $titles): void
    {
        $requestedTitles = $titles->unique('id')->values();

        if ($requestedTitles->isEmpty()) {
            throw EntityNotAvailableException::forMatchAssignment('titles');
        }

        $conflictingEventIds = $this->conflictService->lockConflictingEventIds($lockedMatch);
        $lockedTitles = Title::query()
            ->whereKey($requestedTitles->pluck('id'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($lockedTitles->count() !== $requestedTitles->count() || $lockedTitles->contains(
            fn (Title $title): bool => ! $title->isCurrentlyActive()
        )) {
            throw EntityNotAvailableException::forMatchAssignment('titles');
        }

        $this->conflictService->ensureTitlesCanBeAssigned($conflictingEventIds, $lockedTitles);
        $this->requirements->ensureSatisfied($lockedMatch, $lockedTitles);

        $lockedTitles->each(function (Title $title) use ($lockedMatch): void {
            $lockedMatch->titles()->attach($title->id);
        });
    }
}
