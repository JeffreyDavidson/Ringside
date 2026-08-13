<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class StableFormerMemberEligibility
{
    /** @return Collection<int, Wrestler|TagTeam> */
    public function availableFor(Stable $stable): Collection
    {
        $wrestlers = $stable->previousWrestlers()
            ->whereHas('employments', fn (Builder $employmentQuery): Builder => $employmentQuery->whereNull('ended_at'))
            ->whereDoesntHave('injuries', fn (Builder $injuryQuery): Builder => $injuryQuery->whereNull('ended_at'))
            ->whereDoesntHave('suspensions', fn (Builder $suspensionQuery): Builder => $suspensionQuery->whereNull('ended_at'))
            ->whereDoesntHave('retirements', fn (Builder $retirementQuery): Builder => $retirementQuery->whereNull('ended_at'))
            ->get();

        $tagTeams = $stable->previousTagTeams()
            ->whereHas('employments', fn (Builder $employmentQuery): Builder => $employmentQuery->whereNull('ended_at'))
            ->whereDoesntHave('suspensions', fn (Builder $suspensionQuery): Builder => $suspensionQuery->whereNull('ended_at'))
            ->whereDoesntHave('retirements', fn (Builder $retirementQuery): Builder => $retirementQuery->whereNull('ended_at'))
            ->get();

        return $wrestlers->concat($tagTeams);
    }

    /** @return Collection<int, Wrestler|TagTeam> */
    public function unavailableKeyMembersFor(Stable $stable): Collection
    {
        $wrestlers = $stable->previousWrestlers()
            ->where(function (Builder $wrestlerQuery) use ($stable): void {
                $wrestlerQuery
                    ->whereHas('retirements', fn (Builder $retirementQuery): Builder => $retirementQuery->whereNull('ended_at'))
                    ->orWhereHas('injuries', fn (Builder $injuryQuery): Builder => $injuryQuery->whereNull('ended_at'))
                    ->orWhereHas('suspensions', fn (Builder $suspensionQuery): Builder => $suspensionQuery->whereNull('ended_at'))
                    ->orWhereHas('currentStable', fn (Builder $stableQuery): Builder => $stableQuery->whereKeyNot($stable->getKey()));
            })
            ->get();

        $tagTeams = $stable->previousTagTeams()
            ->where(function (Builder $tagTeamQuery) use ($stable): void {
                $tagTeamQuery
                    ->whereHas('retirements', fn (Builder $retirementQuery): Builder => $retirementQuery->whereNull('ended_at'))
                    ->orWhereHas('suspensions', fn (Builder $suspensionQuery): Builder => $suspensionQuery->whereNull('ended_at'))
                    ->orWhereHas('currentStable', fn (Builder $stableQuery): Builder => $stableQuery->whereKeyNot($stable->getKey()));
            })
            ->get();

        return $wrestlers->concat($tagTeams);
    }
}
