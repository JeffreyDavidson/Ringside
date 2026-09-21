<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Booking;

use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

final class RosterBookingStrategyResolver
{
    public function resolve(Wrestler|Referee|TagTeam $rosterMember): RosterBookingStrategy
    {
        return match (true) {
            $rosterMember instanceof TagTeam => new TagTeamRosterBookingStrategy($rosterMember, $this),
            default => new IndividualRosterBookingStrategy($rosterMember),
        };
    }
}
