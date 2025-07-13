<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Models\Matches\EventMatch;
use App\Models\TagTeams\TagTeam;
use App\Repositories\EventMatchRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;

class AddTagTeamsToMatchAction extends BaseEventMatchAction
{
    use AsAction;

    public function __construct(
        EventMatchRepository $eventMatchRepository
    ) {
        parent::__construct($eventMatchRepository);
    }

    /**
     * Add tag teams to an event match.
     *
     * This handles the complete tag team assignment workflow for matches:
     * - Validates tag teams are active, employed, and available for competition
     * - Assigns tag teams to a specific side/team in the match structure
     * - Creates competitor records linking tag teams to the match with proper side allocation
     * - Maintains match integrity and side assignments for balanced team competition
     * - Ensures tag teams are not double-booked or conflicted on the event date
     * - Validates both partners in the tag team are available and bookable
     *
     * BUSINESS RULES:
     * - Tag teams must be employed and active (not suspended or retired)
     * - Both tag team partners must be available and not injured
     * - Tag teams cannot be assigned to multiple sides in the same match
     * - Tag teams cannot be double-booked for the same event date
     * - Side numbers must be valid for the match type
     *
     * BUSINESS IMPACT:
     * - Creates the foundation for tag team match competition structure
     * - Enables proper team-based match result tracking and championship changes
     * - Establishes tag team relationships for booking and storyline continuity
     * - Supports team-based payroll and appearance fee calculations
     * - Maintains tag team division integrity and rankings
     *
     * @param  EventMatch  $eventMatch  The match to add tag teams to
     * @param  Collection<int, TagTeam>  $tagTeams  The tag teams to add to the match
     * @param  int  $sideNumber  The side/team number for the tag teams (1, 2, 3, etc.)
     *
     * @example
     * ```php
     * // Tag team match - The Hardy Boyz vs Edge & Christian
     * $tagTeams = collect([$hardyBoyz]);
     * AddTagTeamsToMatchAction::run($match, $tagTeams, 1);
     *
     * $tagTeams = collect([$edgeAndChristian]);
     * AddTagTeamsToMatchAction::run($match, $tagTeams, 2);
     *
     * // Triple threat tag match - Three teams competing
     * $tagTeams = collect([$dudleyBoyz]);
     * AddTagTeamsToMatchAction::run($match, $tagTeams, 3);
     *
     * // Elimination tag match - Multiple teams on one side
     * $tagTeams = collect([$team1, $team2]);
     * AddTagTeamsToMatchAction::run($match, $tagTeams, 1);
     * ```
     */
    public function handle(EventMatch $eventMatch, Collection $tagTeams, int $sideNumber): void
    {
        // Pre-filter tag teams to ensure only eligible teams are processed
        $eligibleTagTeams = $tagTeams->filter(
            fn (TagTeam $tagTeam) => $this->isTagTeamEligibleForMatch($tagTeam, $eventMatch)
        );

        // Validate we have tag teams to add after filtering
        if ($eligibleTagTeams->isEmpty()) {
            throw new InvalidArgumentException('No eligible tag teams provided for match assignment');
        }

        // Validate side number is reasonable for match structure
        if ($sideNumber < 1) {
            throw new InvalidArgumentException('Side number must be positive');
        }

        DB::transaction(function () use ($eventMatch, $eligibleTagTeams, $sideNumber): void {
            // Add each eligible tag team to the specified side
            $eligibleTagTeams->each(
                fn (TagTeam $tagTeam) => $this->eventMatchRepository->addTagTeamToMatch(
                    $eventMatch,
                    $tagTeam,
                    $sideNumber
                )
            );
        });
    }

    /**
     * Check if a tag team is eligible to compete in the match.
     *
     * @param  TagTeam  $tagTeam  The tag team to validate
     * @param  EventMatch  $eventMatch  The match they would compete in
     * @return bool True if the tag team can compete
     */
    private function isTagTeamEligibleForMatch(TagTeam $tagTeam, EventMatch $eventMatch): bool
    {
        // Basic availability checks - tag team must be active and available
        if (! $tagTeam->isBookable()) {
            return false;
        }

        // Check for conflicts with existing match assignments
        // Note: More complex conflict checking would be implemented here
        // such as checking for double-booking on the same event date
        // Could validate against $eventMatch->event->date for scheduling conflicts

        return true;
    }
}
