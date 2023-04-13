<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\EventMatchData;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Title;
use App\Models\Wrestler;

class EventMatchRepository
{
    /**
     * Create a new event match for a given event with the given data.
     */
    public function createForEvent(Event $event, EventMatchData $eventMatchData): EventMatch
    {
        return $event->matches()->create([
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
