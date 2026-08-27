<?php

declare(strict_types=1);

namespace App\Services\Roster\Stables;

use App\Data\Stables\StableMembershipData;
use App\Models\Roster\Stables\Stable;
use App\Services\Roster\Relationships\HistoricalMembershipService;
use Illuminate\Support\Carbon;

class StableMembershipService
{
    public function __construct(private HistoricalMembershipService $historicalMemberships) {}

    public function currentMembers(Stable $stable): StableMembershipData
    {
        return new StableMembershipData(
            wrestlers: $stable->currentWrestlers,
            tagTeams: $stable->currentTagTeams,
        );
    }

    public function addMembers(Stable $stable, StableMembershipData $members, Carbon $date): void
    {
        $this->historicalMemberships->add($stable->wrestlers(), $members->wrestlers, $date);
        $this->historicalMemberships->add($stable->tagTeams(), $members->tagTeams, $date);
    }

    public function removeMembers(Stable $stable, StableMembershipData $members, Carbon $date): void
    {
        $this->historicalMemberships->remove($stable->wrestlers(), $members->wrestlers, $date);
        $this->historicalMemberships->remove($stable->tagTeams(), $members->tagTeams, $date);
    }

    public function updateMembership(Stable $stable, StableMembershipData $newMembers, Carbon $date): void
    {
        $this->historicalMemberships->synchronize(
            $stable->wrestlers(),
            $stable->currentWrestlers,
            $newMembers->wrestlers,
            $date,
        );
        $this->historicalMemberships->synchronize(
            $stable->tagTeams(),
            $stable->currentTagTeams,
            $newMembers->tagTeams,
            $date,
        );
    }
}
