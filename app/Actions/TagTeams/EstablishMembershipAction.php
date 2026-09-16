<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\AssignManagersAction;
use App\Data\TagTeams\TagTeamMembershipData;
use App\Models\Roster\TagTeams\TagTeam;
use App\Services\Roster\Relationships\HistoricalMembershipService;
use Illuminate\Support\Carbon;

class EstablishMembershipAction
{
    public function __construct(
        protected HistoricalMembershipService $historicalMemberships,
        protected AssignManagersAction $assignManagersAction,
    ) {}

    public function handle(TagTeam $tagTeam, TagTeamMembershipData $members, Carbon $date): void
    {
        $this->historicalMemberships->add($tagTeam->wrestlers(), $members->wrestlers, $date);
        $this->assignManagersAction->handle($tagTeam, $members->managers, $date);
    }
}
