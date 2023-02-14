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
     * Create a new event with the given data.
     */
    public function createForEvent(Event $event, EventMatchData $eventMatchData): EventMatch
    {
        return $event->matches()->create([
            'match_type_id' => $eventMatchData->matchType->id,
            'preview' => $eventMatchData->preview,
        ]);
    }

    /**
     * Create a new event with the given data.
     */
    public function addTitleToMatch(EventMatch $match, Title $title): EventMatch
    {
        $match->titles()->attach($title);

        return $match;
    }

    /**
     * Create a new event with the given data.
     */
    public function addRefereeToMatch(EventMatch $match, Referee $referee): EventMatch
    {
        $match->referees()->attach($referee);

        return $match;
    }

    /**
     * Create a new event with the given data.
     */
    public function addWrestlerToMatch(EventMatch $match, Wrestler $wrestler, int $sideNumber): void
    {
        $match->wrestlers()->attach($wrestler, ['side_number' => $sideNumber]);
    }

    /**
     * Create a new event with the given data.
     */
    public function addTagTeamToMatch(EventMatch $match, TagTeam $tagTeam, int $sideNumber): EventMatch
    {
        $match->tagTeams()->attach($tagTeam, ['side_number' => $sideNumber]);

        return $match;
    }
}
