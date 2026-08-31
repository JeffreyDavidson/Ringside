<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster;

use App\Enums\Shared\EmploymentStatus;
use App\Lifecycle\Roster\TagTeams\TagTeamMembershipRequirements;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

final class RosterBookingEligibility
{
    public static function allows(Wrestler|Referee|TagTeam $rosterMember): bool
    {
        if ($rosterMember->status !== EmploymentStatus::Employed
            || $rosterMember->currentSuspension()->exists()) {
            return false;
        }

        if ($rosterMember instanceof TagTeam) {
            $currentWrestlers = $rosterMember->currentWrestlers;

            return TagTeamMembershipRequirements::hasMinimumCurrentWrestlers($currentWrestlers)
                && $currentWrestlers->every(
                    fn (Wrestler $wrestler): bool => self::allows($wrestler),
                );
        }

        return ! $rosterMember->currentInjury()->exists();
    }
}
