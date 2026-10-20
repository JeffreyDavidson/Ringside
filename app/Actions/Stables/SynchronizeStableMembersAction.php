<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableMembershipData;
use App\Models\Roster\Stables\Stable;
use Illuminate\Support\Carbon;

class SynchronizeStableMembersAction
{
    public function __construct(
        protected AddStableMembersAction $addStableMembersAction,
        protected RemoveStableMembersAction $removeStableMembersAction,
    ) {}

    public function handle(Stable $stable, StableMembershipData $desiredMembers, Carbon $date): void
    {
        $currentMembers = $stable->currentWrestlers()->inLockOrder()->get();
        $currentTagTeams = $stable->currentTagTeams()->inLockOrder()->get();

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
