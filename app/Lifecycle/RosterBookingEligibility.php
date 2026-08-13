<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

final class RosterBookingEligibility
{
    public static function allows(Wrestler|Referee|TagTeam $rosterMember): bool
    {
        if ($rosterMember instanceof TagTeam) {
            return $rosterMember->currentWrestlers->every(
                fn (Wrestler $wrestler): bool => self::allows($wrestler),
            );
        }

        return ! (
            $rosterMember->isNotInEmployment()
            || $rosterMember->isSuspended()
            || $rosterMember->isInjured()
            || $rosterMember->hasFutureEmployment()
        );
    }
}
