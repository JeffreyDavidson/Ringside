<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Booking;

use App\Builders\Lifecycle\LifecyclePeriodBuilder;
use App\Enums\Shared\EmploymentStatus;
use App\Lifecycle\Roster\TagTeams\TagTeamMembershipRequirements;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

final readonly class TagTeamRosterBookingStrategy implements RosterBookingStrategy
{
    public function __construct(
        private TagTeam $tagTeam,
        private RosterBookingStrategyResolver $strategyResolver,
    ) {}

    public function allows(?Carbon $on = null): bool
    {
        if ($this->tagTeam->isSuspended() || ! $this->teamIsEmployed($on)) {
            return false;
        }

        $currentWrestlers = $this->tagTeam->currentWrestlers;

        return TagTeamMembershipRequirements::hasMinimumCurrentWrestlers($currentWrestlers)
            && $currentWrestlers->every(
                fn (Wrestler $wrestler): bool => $this->strategyResolver->resolve($wrestler)->allows($on),
            );
    }

    private function teamIsEmployed(?Carbon $on): bool
    {
        if (! $on instanceof Carbon) {
            return $this->tagTeam->status === EmploymentStatus::Employed;
        }

        if ($this->tagTeam->status === EmploymentStatus::Retired) {
            return false;
        }

        $employments = $this->tagTeam->employments()->getQuery();
        LifecyclePeriodBuilder::constrainToActiveOn($employments, $on->copy()->utc());

        return $employments->exists();
    }
}
