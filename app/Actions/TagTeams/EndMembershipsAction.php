<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\EndManagerAssignmentsAction;
use App\Models\Roster\TagTeams\TagTeam;
use App\Services\Roster\Relationships\HistoricalMembershipService;
use Illuminate\Support\Carbon;

class EndMembershipsAction
{
    public function __construct(
        protected HistoricalMembershipService $historicalMemberships,
        protected EndManagerAssignmentsAction $endManagerAssignmentsAction,
    ) {}

    public function handle(TagTeam $tagTeam, Carbon $date): void
    {
        $this->historicalMemberships->remove($tagTeam->wrestlers(), $tagTeam->currentWrestlers, $date);
        $this->endManagerAssignmentsAction->handle($tagTeam, $date);
    }
}
