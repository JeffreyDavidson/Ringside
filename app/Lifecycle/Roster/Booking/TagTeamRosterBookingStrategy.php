<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Booking;

use App\Enums\Shared\EmploymentStatus;
use App\Lifecycle\Roster\TagTeams\TagTeamMembershipRequirements;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

final class TagTeamRosterBookingStrategy implements RosterBookingStrategy
{
    public function __construct(
        private readonly TagTeam $tagTeam,
        private readonly RosterBookingStrategyResolver $strategyResolver,
    ) {}

    public function allows(): bool
    {
        if ($this->tagTeam->status !== EmploymentStatus::Employed
            || $this->tagTeam->currentSuspension()->exists()) {
            return false;
        }

        $currentWrestlers = $this->tagTeam->currentWrestlers;

        return TagTeamMembershipRequirements::hasMinimumCurrentWrestlers($currentWrestlers)
            && $currentWrestlers->every(
                fn (Wrestler $wrestler): bool => $this->strategyResolver->resolve($wrestler)->allows(),
            );
    }
}
