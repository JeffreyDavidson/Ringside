<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Lifecycle\RosterBookingEligibility;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use App\Services\MatchAssignmentConflictService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AddTagTeamsToMatchAction
{
    public function __construct(
        protected MatchAssignmentConflictService $conflictService,
    ) {}

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
     */
    public function handle(EventMatch $eventMatch, Collection $tagTeams, int $sideNumber): void
    {
        $requestedTagTeams = $tagTeams->unique('id')->values();

        if ($requestedTagTeams->isEmpty() || $requestedTagTeams->contains(
            fn (TagTeam $tagTeam): bool => ! RosterBookingEligibility::allows($tagTeam)
        )) {
            throw EntityNotAvailableException::forMatchAssignment('tag teams');
        }

        // Validate side number is reasonable for match structure
        if ($sideNumber < 1) {
            throw InvalidMatchConfigurationException::invalidSideNumber($sideNumber);
        }

        DB::transaction(function () use ($eventMatch, $requestedTagTeams, $sideNumber): void {
            $this->conflictService->ensureTagTeamsCanBeAssigned($eventMatch, $requestedTagTeams);
            $side = $eventMatch->sides()->firstOrCreate(['position' => $sideNumber]);

            $requestedTagTeams->each(function (TagTeam $tagTeam) use ($eventMatch, $side): void {
                $eventMatch->competitors()->create([
                    'competitor_id' => $tagTeam->id,
                    'competitor_type' => $tagTeam->getMorphClass(),
                    'match_side_id' => $side->id,
                ]);
            });
        });
    }
}
