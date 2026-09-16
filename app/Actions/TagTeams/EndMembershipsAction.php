<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Models\Roster\TagTeams\TagTeam;
use App\Services\Roster\Relationships\HistoricalMembershipService;
use App\Services\Roster\Relationships\ManagerAssignmentService;
use Illuminate\Support\Carbon;

class EndMembershipsAction
{
    public function __construct(
        protected HistoricalMembershipService $historicalMemberships,
        protected ManagerAssignmentService $managerAssignments,
    ) {}

    public function handle(TagTeam $tagTeam, Carbon $date): void
    {
        $this->historicalMemberships->remove($tagTeam->wrestlers(), $tagTeam->currentWrestlers, $date);
        $this->managerAssignments->endAssignmentsFor($tagTeam, $date);
    }
}
