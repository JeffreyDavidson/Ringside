<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Lifecycle\MatchCompetitorRequirements;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AddCompetitorsToMatchAction
{
    public function __construct(
        protected AddTagTeamsToMatchAction $addTagTeamsToMatchAction,
        protected AddWrestlersToMatchAction $addWrestlersToMatchAction,
        private readonly MatchCompetitorRequirements $requirements,
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
     * @param  EventMatch  $eventMatch  The match to add competitors to
     * @param  Collection<int, covariant array{wrestlers?: array<int, Wrestler>, tag_teams?: array<int, TagTeam>}>  $competitors  Competitors organized by side number and type
     */
    public function handle(EventMatch $eventMatch, Collection $competitors): void
    {
        DB::transaction(function () use ($eventMatch, $competitors): void {
            $lockedMatch = EventMatch::query()->whereKey($eventMatch->id)->lockForUpdate()->firstOrFail();
            $this->requirements->ensureSatisfied($lockedMatch, $competitors);

            // Process each side and add competitors
            foreach ($competitors as $sideNumber => $sideCompetitors) {
                $this->addSideCompetitors($lockedMatch, (int) $sideNumber, $sideCompetitors);
            }
        });
    }

    /**
     * Add competitors for a specific side of the match.
     *
     * @param  EventMatch  $eventMatch  The match to add competitors to
     * @param  int  $sideNumber  The side number (1, 2, 3, etc.)
     * @param  array{wrestlers?: array<int, Wrestler>, tag_teams?: array<int, TagTeam>}  $sideCompetitors  Competitors for this side
     */
    private function addSideCompetitors(EventMatch $eventMatch, int $sideNumber, array $sideCompetitors): void
    {
        // Add wrestlers to this side
        $wrestlers = $sideCompetitors['wrestlers'] ?? [];

        if ($wrestlers !== []) {
            $this->addWrestlersToMatchAction->handleWithinTransaction(
                $eventMatch,
                collect($wrestlers),
                $sideNumber
            );
        }

        // Add tag teams to this side
        $tagTeams = $sideCompetitors['tag_teams'] ?? [];

        if ($tagTeams !== []) {
            $this->addTagTeamsToMatchAction->handleWithinTransaction(
                $eventMatch,
                collect($tagTeams),
                $sideNumber
            );
        }
    }
}
