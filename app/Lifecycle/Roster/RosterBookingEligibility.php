<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster;

use App\Lifecycle\Roster\Booking\RosterBookingStrategyResolver;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

final readonly class RosterBookingEligibility
{
    public function __construct(private RosterBookingStrategyResolver $strategyResolver) {}

    public function allows(Wrestler|Referee|TagTeam $rosterMember): bool
    {
        return $this->strategyResolver->resolve($rosterMember)->allows();
    }
}
