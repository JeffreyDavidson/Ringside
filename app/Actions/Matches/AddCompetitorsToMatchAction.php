<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Lifecycle\Matches\MatchCompetitorRequirements;
use App\Lifecycle\Roster\Booking\MatchRosterLock;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\ValueObjects\MatchSides;

class AddCompetitorsToMatchAction
{
    public function __construct(
        protected AddTagTeamsToMatchAction $addTagTeamsToMatchAction,
        protected AddWrestlersToMatchAction $addWrestlersToMatchAction,
        private readonly MatchCompetitorRequirements $requirements,
        private readonly MatchRosterLock $rosterLock,
    ) {}

    /**
     * Add competitors to an event match.
     *
     * This handles the complete competitor assignment workflow with validation:
     * - Validates competitor distribution meets match requirements
     * - Processes competitors organized by sides/teams for proper match structure
     * - Adds individual wrestlers to their assigned sides with conflict checking
     * - Adds tag teams to their assigned sides ensuring team availability
     * - Maintains match integrity and competition balance
     * - Ensures all competitors are available for the event date
     *
     * BUSINESS RULES:
     * - Competitor side and entrant counts must satisfy the selected match type
     * - Wrestlers cannot be assigned to multiple sides in the same match
     * - Tag teams must be active and available for competition
     * - Competitors must not have conflicting bookings on the event date
     *
     * Runs inside the caller's transaction: the caller has locked the match and its event set with
     * MatchSchedulingLock::lockMatchWithEventSet() and checked that every participant belongs to the event's
     * promotion (MatchConfigurationRequirements::ensureWithinEventPromotion()).
     *
     * @param  MatchSides  $competitors  Competitors organized by side number and type
     * @param  list<string>  $retainedKeys  Server-derived retained assignments.
     */
    public function handle(EventMatch $lockedMatch, MatchSides $competitors, array $retainedKeys = []): void
    {
        $this->rosterLock->forSides($competitors);
        $this->requirements->ensureSatisfied($lockedMatch, $competitors);

        foreach ($competitors->sides() as $sideNumber => $sideCompetitors) {
            $this->addSideCompetitors($lockedMatch, (int) $sideNumber, $sideCompetitors, $retainedKeys);
        }
    }

    /**
     * Add competitors for a specific side of the match.
     *
     * @param  EventMatch  $eventMatch  The match to add competitors to
     * @param  int  $sideNumber  The side number (1, 2, 3, etc.)
     * @param  array{wrestlers: list<Wrestler>, tag_teams: list<TagTeam>}  $sideCompetitors  Competitors for this side
     * @param  list<string>  $retainedKeys
     */
    private function addSideCompetitors(EventMatch $eventMatch, int $sideNumber, array $sideCompetitors, array $retainedKeys): void
    {
        // Add wrestlers to this side
        $wrestlers = $sideCompetitors['wrestlers'];

        if ($wrestlers !== []) {
            $this->addWrestlersToMatchAction->handle(
                $eventMatch,
                collect($wrestlers),
                $sideNumber,
                $retainedKeys,
            );
        }

        // Add tag teams to this side
        $tagTeams = $sideCompetitors['tag_teams'];

        if ($tagTeams !== []) {
            $this->addTagTeamsToMatchAction->handle(
                $eventMatch,
                collect($tagTeams),
                $sideNumber,
                $retainedKeys,
            );
        }
    }
}
