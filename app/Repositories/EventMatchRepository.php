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
     *
     * @param  \App\Models\Event  $event
     * @param  \App\Data\EventMatchData  $eventMatchData
     * @return \App\Models\EventMatch
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
     *
     * @param  \App\Models\EventMatch  $match
     * @param  \App\Models\Title  $title
     * @return \App\Models\EventMatch
     */
    public function addTitleToMatch(EventMatch $match, Title $title): EventMatch
    {
        $match->titles()->attach($title);

        return $match;
    }

    /**
     * Create a new event with the given data.
     *
     * @param  \App\Models\EventMatch  $match
     * @param  \App\Models\Referee  $referee
     * @return \App\Models\EventMatch
     */
    public function addRefereeToMatch(EventMatch $match, Referee $referee): EventMatch
    {
        $match->referees()->attach($referee);

        return $match;
    }

    /**
     * Create a new event with the given data.
     *
     * @param  \App\Models\EventMatch  $match
     * @param  \App\Models\Wrestler  $wrestler
     * @param  int  $sideNumber
     * @return void
     */
    public function addWrestlerToMatch(EventMatch $match, Wrestler $wrestler, int $sideNumber): void
    {
        $match->wrestlers()->attach($wrestler, ['side_number' => $sideNumber]);
    }

    /**
     * Create a new event with the given data.
     *
     * @param  \App\Models\EventMatch  $match
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  int  $sideNumber
     * @return \App\Models\EventMatch
     */
    public function addTagTeamToMatch(EventMatch $match, TagTeam $tagTeam, int $sideNumber): EventMatch
    {
        $match->tagTeams()->attach($tagTeam, ['side_number' => $sideNumber]);

        return $match;
    }
}
