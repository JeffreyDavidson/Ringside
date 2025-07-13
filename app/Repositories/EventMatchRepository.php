<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\Matches\EventMatchData;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\Contracts\EventMatchRepositoryInterface;
use Tests\Unit\Repositories\EventMatchRepositoryTest;

/**
 * Repository for EventMatch model business operations and data persistence.
 *
 * Handles all event match related database operations including match creation,
 * participant management (wrestlers, tag teams, referees, titles), and
 * side number assignment for competitive match organization.
 *
 * @see EventMatchRepositoryTest
 */
class EventMatchRepository implements EventMatchRepositoryInterface
{
    /**
     * Create a new event match for a given event with the given data.
     */
    public function createForEvent(Event $event, EventMatchData $eventMatchData): EventMatch
    {
        $matchNumber = $event->matches()->count() + 1;

        return $event->matches()->create([
            'match_number' => $matchNumber,
            'match_type_id' => $eventMatchData->matchType->id,
            'preview' => $eventMatchData->preview,
        ]);
    }

    /**
     * Add a title to an event match.
     */
    public function addTitleToMatch(EventMatch $match, Title $title): void
    {
        $match->titles()->attach($title);
    }

    /**
     * Add a referee to an event match.
     */
    public function addRefereeToMatch(EventMatch $match, Referee $referee): void
    {
        $match->referees()->attach($referee);
    }

    /**
     * Add a wrestler to an event match.
     */
    public function addWrestlerToMatch(EventMatch $match, Wrestler $wrestler, int $sideNumber): void
    {
        $match->wrestlers()->attach($wrestler, ['side_number' => $sideNumber]);
    }

    /**
     * Add a tag team to an event match.
     */
    public function addTagTeamToMatch(EventMatch $match, TagTeam $tagTeam, int $sideNumber): void
    {
        $match->tagTeams()->attach($tagTeam, ['side_number' => $sideNumber]);
    }
}
