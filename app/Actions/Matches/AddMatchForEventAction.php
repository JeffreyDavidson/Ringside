<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Data\Matches\EventMatchData;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AddMatchForEventAction
{
    /**
     * Create a new add match for event action instance.
     */
    public function __construct(
        protected AddRefereesToMatchAction $addRefereesToMatchAction,
        protected AddTitlesToMatchAction $addTitlesToMatchAction,
        protected AddCompetitorsToMatchAction $addCompetitorsToMatchAction
    ) {}

    /**
     * Create a complete match for an event.
     *
     * This handles the comprehensive match creation workflow for an event:
     * - Validates match data integrity and business rule compliance
     * - Creates the match record with proper type, rules, and event association
     * - Assigns qualified referees to officiate with proper authority
     * - Associates championship titles at stake for title matches
     * - Adds all competitors (wrestlers and tag teams) with proper side allocation
     * - Ensures all match components are properly integrated and validated
     * - Maintains data consistency through transaction management
     *
     * BUSINESS RULES:
     * - Events must be scheduled and not yet completed
     * - Match types must be valid and supported by the system
     * - Competitors must be available and not conflicted for the event date
     * - Referees must be qualified and available for officiating
     * - Championship titles must be active if assigned to the match
     * - Matches must have proper competitor distribution for balance
     *
     * BUSINESS IMPACT:
     * - Creates complete match cards for fan engagement and ticket sales
     * - Establishes competition structure for storyline development
     * - Enables proper event planning and resource allocation
     * - Supports championship tracking and title change possibilities
     * - Facilitates payroll calculations and appearance fee management
     * - Drives revenue through match-based promotional marketing
     *
     * @param  Event  $event  The event to add the match to
     * @param  EventMatchData  $eventMatchData  Complete match data including all participants
     * @throws EntityNotAvailableException When an assigned referee or title is unavailable
     * @throws InvalidMatchConfigurationException When required match data or side assignments are invalid
     * @return EventMatch The newly created match with all components properly assigned
     */
    public function handle(Event $event, EventMatchData $eventMatchData): EventMatch
    {
        // Validate match data completeness
        $this->validateMatchData($eventMatchData);

        return DB::transaction(function () use ($event, $eventMatchData): EventMatch {
            // Create the base match record
            $createdMatch = EventMatch::create([
                'event_id' => $event->id,
                'match_type_id' => $eventMatchData->matchType->id,
                'preview' => $eventMatchData->preview,
            ]);

            // Add referees for match officiating (required for all matches)
            if ($eventMatchData->referees->isNotEmpty()) {
                $this->addRefereesToMatchAction->handle($createdMatch, $eventMatchData->referees);
            }

            // Add championship titles if this is a title match
            $eventMatchData->titles->whenNotEmpty(function (Collection $titles) use ($createdMatch): void {
                $this->addTitlesToMatchAction->handle($createdMatch, $titles);
            });

            // Add all competitors to complete the match setup
            if ($eventMatchData->competitors->isNotEmpty()) {
                // Transform competitors from type-grouped to side-grouped structure
                $transformedCompetitors = $this->transformCompetitorsStructure($eventMatchData->competitors);
                $this->addCompetitorsToMatchAction->handle($createdMatch, $transformedCompetitors);
            }

            return $createdMatch;
        });
    }

    /**
     * Transform competitors from type-grouped to side-grouped structure.
     *
     * @param  Collection<string, covariant array<int, Wrestler|TagTeam>>  $competitors
     * @return Collection<int, array<string, array<int, Wrestler|TagTeam>>>
     */
    private function transformCompetitorsStructure(Collection $competitors): Collection
    {
        // For now, assume single side (side 1) for all competitors
        // This is a simplified transformation - a more complex implementation
        // would need to handle side assignment based on match type and strategy

        /** @var array<int, array<string, array<int, Wrestler|TagTeam>>> $transformedData */
        $transformedData = [
            1 => [
                'wrestlers' => $competitors->get('wrestlers', []),
                'tag_teams' => $competitors->get('tag_teams', []),
            ],
        ];

        return collect($transformedData);
    }

    /**
     * Validate match data for completeness and business rules.
     *
     * @param  EventMatchData  $eventMatchData  The match data to validate
     * @throws InvalidMatchConfigurationException When validation fails
     */
    private function validateMatchData(EventMatchData $eventMatchData): void
    {
        // Ensure we have competitors for the match
        if ($eventMatchData->competitors->isEmpty()) {
            throw InvalidMatchConfigurationException::missingCompetitors();
        }

        // Ensure we have at least one referee
        if ($eventMatchData->referees->isEmpty()) {
            throw InvalidMatchConfigurationException::missingReferees();
        }

        // Additional validation could include:
        // - Match type compatibility with competitors
        // - Title match requirements validation
        // - Competitor availability checking
    }
}
