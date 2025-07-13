<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Models\Matches\EventMatch;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\EventMatchRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;

class AddCompetitorsToMatchAction extends BaseEventMatchAction
{
    use AsAction;

    public function __construct(
        EventMatchRepository $eventMatchRepository
    ) {
        parent::__construct($eventMatchRepository);
    }

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
     * - Matches must have at least 2 sides with competitors
     * - Wrestlers cannot be assigned to multiple sides in the same match
     * - Tag teams must be active and available for competition
     * - Competitors must not have conflicting bookings on the event date
     *
     * @param  EventMatch  $eventMatch  The match to add competitors to
     * @param  Collection<int, array<string, array<int, Wrestler|TagTeam>>>  $competitors  Competitors organized by side number and type
     *
     * @example
     * ```php
     * // Singles match: John Cena vs Randy Orton
     * $competitors = collect([
     *     1 => ['wrestlers' => [$johnCena], 'tag_teams' => []],
     *     2 => ['wrestlers' => [$randyOrton], 'tag_teams' => []]
     * ]);
     * AddCompetitorsToMatchAction::run($match, $competitors);
     *
     * // Tag team match: The Hardy Boyz vs Edge & Christian
     * $competitors = collect([
     *     1 => ['wrestlers' => [], 'tag_teams' => [$hardyBoyz]],
     *     2 => ['wrestlers' => [], 'tag_teams' => [$edgeAndChristian]]
     * ]);
     * AddCompetitorsToMatchAction::run($match, $competitors);
     *
     * // Triple threat match: Stone Cold vs The Rock vs Triple H
     * $competitors = collect([
     *     1 => ['wrestlers' => [$stoneColid], 'tag_teams' => []],
     *     2 => ['wrestlers' => [$theRock], 'tag_teams' => []],
     *     3 => ['wrestlers' => [$tripleH], 'tag_teams' => []]
     * ]);
     * AddCompetitorsToMatchAction::run($match, $competitors);
     * ```
     */
    public function handle(EventMatch $eventMatch, Collection $competitors): void
    {
        // Validate competitor distribution before processing
        $competitorArray = $competitors->toArray();
        if (! $this->validateCompetitorDistribution($competitorArray)) {
            throw new InvalidArgumentException('Match must have at least 2 sides with competitors');
        }

        DB::transaction(function () use ($eventMatch, $competitors): void {
            // Process each side and add competitors
            foreach ($competitors as $sideNumber => $sideCompetitors) {
                $this->addSideCompetitors($eventMatch, (int) $sideNumber, $sideCompetitors);
            }
        });
    }

    /**
     * Add competitors for a specific side of the match.
     *
     * @param  EventMatch  $eventMatch  The match to add competitors to
     * @param  int  $sideNumber  The side number (1, 2, 3, etc.)
     * @param  array<string, array<int, Wrestler|TagTeam>>  $sideCompetitors  Competitors for this side
     */
    private function addSideCompetitors(EventMatch $eventMatch, int $sideNumber, array $sideCompetitors): void
    {
        // Add wrestlers to this side
        if (Arr::exists($sideCompetitors, 'wrestlers') && ! empty($sideCompetitors['wrestlers'])) {
            resolve(AddWrestlersToMatchAction::class)->handle(
                $eventMatch,
                collect((array) Arr::get($sideCompetitors, 'wrestlers')),
                $sideNumber
            );
        }

        // Add tag teams to this side
        if (Arr::exists($sideCompetitors, 'tag_teams') && ! empty($sideCompetitors['tag_teams'])) {
            resolve(AddTagTeamsToMatchAction::class)->handle(
                $eventMatch,
                collect((array) Arr::get($sideCompetitors, 'tag_teams')),
                $sideNumber
            );
        }
    }
}
