<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\SynchronizeManagerAssignmentsAction;
use App\Data\TagTeams\TagTeamMembershipData;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class SynchronizeMembershipAction
{
    public function __construct(
        protected SynchronizeManagerAssignmentsAction $synchronizeManagerAssignmentsAction,
    ) {}

    public function handle(TagTeam $tagTeam, TagTeamMembershipData $members, Carbon $date): void
    {
        if ($members->wrestlers instanceof Collection) {
            $currentWrestlers = $tagTeam->currentWrestlers;

            foreach ($currentWrestlers->diff($members->wrestlers) as $wrestler) {
                $tagTeam->wrestlers()->newPivotStatementForId($wrestler->getKey())
                    ->whereNull('left_at')
                    ->update(['left_at' => $date]);
            }

            $newWrestlers = $members->wrestlers->diff($currentWrestlers);
            if ($newWrestlers->isNotEmpty()) {
                $tagTeam->wrestlers()->attach($newWrestlers->map(
                    fn (Wrestler $wrestler): int => Arr::integer(['key' => $wrestler->getKey()], 'key'),
                )->all(), [
                    'joined_at' => $date,
                    'left_at' => null,
                ]);
            }
        }

        $this->synchronizeManagerAssignmentsAction->handle($tagTeam, $members->managers, $date);
    }
}
