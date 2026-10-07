<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Stables;

use App\Data\Stables\StableMembershipData;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class StableFormerMemberEligibility
{
    /** @return Collection<int, Wrestler|TagTeam> */
    public function availableFor(Stable $stable): Collection
    {
        $members = $this->availableMembersFor($stable);

        return collect([...($members->wrestlers ?? []), ...($members->tagTeams ?? [])]);
    }

    /**
     * Former members that can return to the stable, once each however often they joined and left.
     */
    public function availableMembersFor(Stable $stable): StableMembershipData
    {
        $wrestlers = $stable->previousWrestlers()
            ->whereHas('currentEmployment')
            ->whereDoesntHave('currentInjury')
            ->whereDoesntHave('currentSuspension')
            ->whereDoesntHave('currentRetirement')
            ->get()
            ->unique('id')
            ->values();

        $tagTeams = $stable->previousTagTeams()
            ->whereHas('currentEmployment')
            ->whereDoesntHave('currentSuspension')
            ->whereDoesntHave('currentRetirement')
            ->get()
            ->unique('id')
            ->values();

        return new StableMembershipData($wrestlers, $tagTeams);
    }

    /** @return Collection<int, Wrestler|TagTeam> */
    public function unavailableKeyMembersFor(Stable $stable): Collection
    {
        $wrestlers = $stable->previousWrestlers()
            ->where(function (Builder $wrestlerQuery) use ($stable): void {
                $wrestlerQuery
                    ->whereHas('currentRetirement')
                    ->orWhereHas('currentInjury')
                    ->orWhereHas('currentSuspension')
                    ->orWhereHas('currentStable', fn (Builder $stableQuery): Builder => $stableQuery->whereKeyNot($stable->getKey()));
            })
            ->get();

        $tagTeams = $stable->previousTagTeams()
            ->where(function (Builder $tagTeamQuery) use ($stable): void {
                $tagTeamQuery
                    ->whereHas('currentRetirement')
                    ->orWhereHas('currentSuspension')
                    ->orWhereHas('currentStable', fn (Builder $stableQuery): Builder => $stableQuery->whereKeyNot($stable->getKey()));
            })
            ->get();

        return $wrestlers->concat($tagTeams);
    }
}
