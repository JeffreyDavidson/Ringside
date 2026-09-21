<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableMembershipData;
use App\Models\Roster\Stables\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SynchronizeStableMembersAction
{
    public function __construct(
        protected AddStableMembersAction $addStableMembersAction,
        protected RemoveStableMembersAction $removeStableMembersAction,
    ) {}

    public function handle(Stable $stable, StableMembershipData $desiredMembers, Carbon $date): void
    {
        $currentMembers = $stable->currentWrestlers;
        $currentTagTeams = $stable->currentTagTeams;

        if ($desiredMembers->wrestlers instanceof Collection) {
            $this->removeStableMembersAction->handle(
                $stable,
                new StableMembershipData(wrestlers: $currentMembers->diff($desiredMembers->wrestlers)),
                $date,
            );
            $this->addStableMembersAction->handle(
                $stable,
                new StableMembershipData(wrestlers: $desiredMembers->wrestlers->diff($currentMembers)),
                $date,
            );
        }

        if ($desiredMembers->tagTeams instanceof Collection) {
            $this->removeStableMembersAction->handle(
                $stable,
                new StableMembershipData(tagTeams: $currentTagTeams->diff($desiredMembers->tagTeams)),
                $date,
            );
            $this->addStableMembersAction->handle(
                $stable,
                new StableMembershipData(tagTeams: $desiredMembers->tagTeams->diff($currentTagTeams)),
                $date,
            );
        }
    }
}
