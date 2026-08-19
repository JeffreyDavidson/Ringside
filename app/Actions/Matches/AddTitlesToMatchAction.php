<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Services\MatchAssignmentConflictService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AddTitlesToMatchAction
{
    public function __construct(
        protected MatchAssignmentConflictService $conflictService,
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

        if ($requestedTitles->isEmpty() || $requestedTitles->contains(
            fn (Title $title): bool => ! $title->isCurrentlyActive()
        )) {
            throw EntityNotAvailableException::forMatchAssignment('titles');
        }

        DB::transaction(function () use ($eventMatch, $requestedTitles): void {
            $this->conflictService->ensureTitlesCanBeAssigned($eventMatch, $requestedTitles);
            $this->ensureTitleTypesMatchCompetitors($eventMatch, $requestedTitles);
            $this->ensureCurrentChampionsCompete($eventMatch, $requestedTitles);

            $requestedTitles->each(function (Title $title) use ($eventMatch): void {
                $eventMatch->titles()->attach($title->id);
            });
        });
    }

    /** @param Collection<int, Title> $titles */
    private function ensureTitleTypesMatchCompetitors(EventMatch $eventMatch, Collection $titles): void
    {
        $assignedCompetitorTypes = $eventMatch->competitors()
            ->pluck('competitor_type')
            ->unique();

        foreach ($titles as $title) {
            if ($assignedCompetitorTypes->count() === 1
                && $assignedCompetitorTypes->contains($title->type->championMorphClass())) {
                continue;
            }

            throw InvalidMatchConfigurationException::titleCompetitorTypeMismatch($title);
        }
    }

    /** @param Collection<int, Title> $titles */
    private function ensureCurrentChampionsCompete(EventMatch $eventMatch, Collection $titles): void
    {
        $assignedCompetitors = $eventMatch->competitors()
            ->get(['competitor_type', 'competitor_id']);
        $currentChampionships = TitleChampionship::query()
            ->whereIn('title_id', $titles->pluck('id'))
            ->current()
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'title_id', 'champion_type', 'champion_id']);

        foreach ($currentChampionships as $championship) {
            $championCompetes = $assignedCompetitors->contains(
                fn (MatchCompetitor $competitor): bool => $competitor->competitor_type === $championship->champion_type
                    && $competitor->competitor_id === $championship->champion_id,
            );

            if ($championCompetes) {
                continue;
            }

            $title = $titles->firstWhere('id', $championship->title_id);

            if ($title instanceof Title) {
                throw InvalidMatchConfigurationException::currentChampionMissing($title);
            }
        }
    }
}
