<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\TagTeams\TagTeamMembershipData;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Carbon;

class TagTeamMembershipService
{
    public function __construct(
        private ManagerAssignmentService $managerAssignments,
        private HistoricalMembershipService $historicalMemberships,
    ) {}

    public function establishMembership(TagTeam $tagTeam, TagTeamMembershipData $members, Carbon $date): void
    {
        $this->historicalMemberships->add(
            $tagTeam->wrestlers(),
            $members->wrestlers,
            $date,
        );
        $this->managerAssignments->assign($tagTeam, $members->managers, $date);
    }

    public function updateMembership(TagTeam $tagTeam, TagTeamMembershipData $members, Carbon $date): void
    {
        $this->historicalMemberships->synchronize(
            $tagTeam->wrestlers(),
            $tagTeam->currentWrestlers,
            $members->wrestlers,
            $date,
        );
        $this->managerAssignments->synchronize($tagTeam, $members->managers, $date);
    }
}
