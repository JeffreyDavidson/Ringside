<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\AssignManagersAction;
use App\Data\TagTeams\TagTeamMembershipData;
use App\Lifecycle\Roster\TagTeams\IncomingWrestlersLock;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Carbon;

class EstablishMembershipAction
{
    public function __construct(
        protected AssignManagersAction $assignManagersAction,
        protected IncomingWrestlersLock $lockIncomingWrestlers,
    ) {}

    public function handle(TagTeam $tagTeam, TagTeamMembershipData $members, Carbon $date): void
    {
        if ($members->wrestlers->isNotEmpty()) {
            $this->lockIncomingWrestlers->lock($tagTeam, $members->wrestlers);

            $tagTeam->wrestlers()->attach($members->wrestlers->modelKeys(), [
                'joined_at' => $date,
                'left_at' => null,
            ]);
        }

        $this->assignManagersAction->handle($tagTeam, $members->managers, $date);
    }
}
