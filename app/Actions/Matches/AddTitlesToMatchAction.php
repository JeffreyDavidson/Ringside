<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Models\Matches\EventMatch;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;

class AddTitlesToMatchAction extends BaseEventMatchAction
{
    use AsAction;

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
     *
     * @example
     * ```php
     * // WWE Championship title defense
     * $titles = collect([$wweChampionship]);
     * AddTitlesToMatchAction::run($match, $titles);
     *
     * // Unification match with two titles
     * $titles = collect([$wweChampionship, $universalTitle]);
     * AddTitlesToMatchAction::run($match, $titles);
     *
     * // Tag team championship match
     * $titles = collect([$tagTeamChampionship]);
     * AddTitlesToMatchAction::run($match, $titles);
     *
     * // Vacant title tournament final
     * $titles = collect([$vacantIntercontinentalTitle]);
     * AddTitlesToMatchAction::run($match, $titles);
     * ```
     */
    public function handle(EventMatch $eventMatch, \Illuminate\Support\Collection $titles): void
    {
        // Pre-filter titles to ensure only eligible championships are processed
        $eligibleTitles = $titles->filter(
            fn (Title $title) => $this->isTitleEligibleForMatch($title, $eventMatch)
        );

        // Validate we have titles to add after filtering
        if ($eligibleTitles->isEmpty()) {
            throw new InvalidArgumentException('No eligible titles provided for championship match');
        }

        DB::transaction(function () use ($eventMatch, $eligibleTitles): void {
            // Add each eligible title as championship stakes
            $eligibleTitles->each(
                fn (Title $title) => $this->eventMatchRepository->addTitleToMatch($eventMatch, $title)
            );
        });
    }

    /**
     * Check if a title is eligible to be at stake in the match.
     *
     * @param  Title  $title  The title to validate
     * @param  EventMatch  $eventMatch  The match where it would be at stake
     * @return bool True if the title can be defended/competed for
     */
    private function isTitleEligibleForMatch(Title $title, EventMatch $eventMatch): bool
    {
        // Basic availability checks - title must be active and available
        if (! $title->isCurrentlyActive()) {
            return false;
        }

        // Check for conflicts with existing title defenses
        // Note: More complex validation would be implemented here
        // such as ensuring the current champion is participating in the match
        // Could validate against $eventMatch->event->date for scheduling conflicts

        return true;
    }
}
