<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\SynchronizeManagerAssignmentsAction;
use App\Data\TagTeams\TagTeamMembershipData;
use App\Lifecycle\Periods\OpenPeriodEnder;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class SynchronizeMembershipAction
{
    public function __construct(
        protected SynchronizeManagerAssignmentsAction $synchronizeManagerAssignmentsAction,
        protected LockIncomingWrestlersAction $lockIncomingWrestlersAction,
    ) {}

    public function handle(TagTeam $tagTeam, TagTeamMembershipData $members, Carbon $date): void
    {
        if ($members->wrestlers instanceof Collection) {
            $currentWrestlers = $tagTeam->currentWrestlers()->inLockOrder()->get();

            foreach ($currentWrestlers->diff($members->wrestlers) as $wrestler) {
                OpenPeriodEnder::end(
                    $tagTeam->wrestlers()->newPivotStatementForId($wrestler->getKey()),
                    'joined_at',
                    'left_at',
                    $date,
                );
            }

            $newWrestlers = $members->wrestlers->diff($currentWrestlers);
            if ($newWrestlers->isNotEmpty()) {
                $this->lockIncomingWrestlersAction->handle($tagTeam, $newWrestlers);

                $tagTeam->wrestlers()->attach($newWrestlers->modelKeys(), [
                    'joined_at' => $date,
                    'left_at' => null,
                ]);
            }
        }

        $this->synchronizeManagerAssignmentsAction->handle($tagTeam, $members->managers, $date);
    }
}
