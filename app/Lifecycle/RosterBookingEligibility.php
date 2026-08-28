<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Lifecycle\Roster\TagTeams\TagTeamMembershipRequirements;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

final class RosterBookingEligibility
{
    public static function allows(Wrestler|Referee|TagTeam $rosterMember): bool
    {
        if ($rosterMember instanceof TagTeam) {
            if (
                $rosterMember->hasNoCurrentOrFutureEmployment()
                || $rosterMember->isSuspended()
                || $rosterMember->isRetired()
                || $rosterMember->hasFutureEmployment()
            ) {
                return false;
            }

            $currentWrestlers = $rosterMember->currentWrestlers;

            return TagTeamMembershipRequirements::hasMinimumCurrentWrestlers($currentWrestlers)
                && $currentWrestlers->every(
                    fn (Wrestler $wrestler): bool => self::allows($wrestler),
                );
        }

        return ! (
            $rosterMember->hasNoCurrentOrFutureEmployment()
            || $rosterMember->isSuspended()
            || $rosterMember->isInjured()
            || $rosterMember->hasFutureEmployment()
        );
    }
}
