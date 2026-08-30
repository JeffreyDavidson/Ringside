<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster;

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
                (! $rosterMember->currentEmployment()->exists() && ! $rosterMember->futureEmployment()->exists())
                || $rosterMember->isSuspended()
                || $rosterMember->currentRetirement()->exists()
                || $rosterMember->futureEmployment()->exists()
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
            (! $rosterMember->currentEmployment()->exists() && ! $rosterMember->futureEmployment()->exists())
            || $rosterMember->isSuspended()
            || $rosterMember->isInjured()
            || $rosterMember->futureEmployment()->exists()
        );
    }
}
