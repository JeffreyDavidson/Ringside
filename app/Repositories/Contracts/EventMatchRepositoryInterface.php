<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\Matches\EventMatchData;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;

interface EventMatchRepositoryInterface
{
    // CRUD operations
    public function createForEvent(Event $event, EventMatchData $eventMatchData): EventMatch;

    // Relationship operations
    public function addTitleToMatch(EventMatch $match, Title $title): void;

    public function addRefereeToMatch(EventMatch $match, Referee $referee): void;

    public function addWrestlerToMatch(EventMatch $match, Wrestler $wrestler, int $sideNumber): void;

    public function addTagTeamToMatch(EventMatch $match, TagTeam $tagTeam, int $sideNumber): void;
}
