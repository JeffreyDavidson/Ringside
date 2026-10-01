<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\AssignManagersAction;
use App\Data\TagTeams\TagTeamMembershipData;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class EstablishMembershipAction
{
    public function __construct(
        protected AssignManagersAction $assignManagersAction,
        protected LockIncomingWrestlersAction $lockIncomingWrestlersAction,
    ) {}

    public function handle(TagTeam $tagTeam, TagTeamMembershipData $members, Carbon $date): void
    {
        if ($members->wrestlers instanceof Collection && $members->wrestlers->isNotEmpty()) {
            $this->lockIncomingWrestlersAction->handle($tagTeam, $members->wrestlers);

            $tagTeam->wrestlers()->attach($members->wrestlers->modelKeys(), [
                'joined_at' => $date,
                'left_at' => null,
            ]);
        }

        $this->assignManagersAction->handle($tagTeam, $members->managers, $date);
    }
}
