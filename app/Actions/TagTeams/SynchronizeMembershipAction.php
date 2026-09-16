<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\SynchronizeManagerAssignmentsAction;
use App\Data\TagTeams\TagTeamMembershipData;
use App\Models\Roster\TagTeams\TagTeam;
use App\Services\Roster\Relationships\HistoricalMembershipService;
use Illuminate\Support\Carbon;

class SynchronizeMembershipAction
{
    public function __construct(
        protected HistoricalMembershipService $historicalMemberships,
        protected SynchronizeManagerAssignmentsAction $synchronizeManagerAssignmentsAction,
    ) {}

    public function handle(TagTeam $tagTeam, TagTeamMembershipData $members, Carbon $date): void
    {
        $this->historicalMemberships->synchronize($tagTeam->wrestlers(), $tagTeam->currentWrestlers, $members->wrestlers, $date);
        $this->synchronizeManagerAssignmentsAction->handle($tagTeam, $members->managers, $date);
    }
}
