<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Collection;

/** @mixin Stable */
trait FindsAvailableStableFormerMembers
{
    /** @return Collection<int, Wrestler|TagTeam> */
    public function getAvailableFormerMembers(): Collection
    {
        $wrestlers = $this->previousWrestlers()
            ->whereHas('employments', fn ($employmentQuery) => $employmentQuery->whereNull('ended_at'))
            ->whereDoesntHave('injuries', fn ($injuryQuery) => $injuryQuery->whereNull('ended_at'))
            ->whereDoesntHave('suspensions', fn ($suspensionQuery) => $suspensionQuery->whereNull('ended_at'))
            ->whereDoesntHave('retirements', fn ($retirementQuery) => $retirementQuery->whereNull('ended_at'))
            ->get();

        $tagTeams = $this->previousTagTeams()
            ->whereHas('employments', fn ($employmentQuery) => $employmentQuery->whereNull('ended_at'))
            ->whereDoesntHave('suspensions', fn ($suspensionQuery) => $suspensionQuery->whereNull('ended_at'))
            ->whereDoesntHave('retirements', fn ($retirementQuery) => $retirementQuery->whereNull('ended_at'))
            ->get();

        return $wrestlers->concat($tagTeams);
    }

    /** @return Collection<int, Wrestler|TagTeam> */
    public function getUnavailableKeyFormerMembers(): Collection
    {
        $wrestlers = $this->previousWrestlers()
            ->where(function ($wrestlerQuery): void {
                $wrestlerQuery->whereHas('retirements', fn ($retirementQuery) => $retirementQuery->whereNull('ended_at'))
                    ->orWhereHas('injuries', fn ($injuryQuery) => $injuryQuery->whereNull('ended_at'))
                    ->orWhereHas('suspensions', fn ($suspensionQuery) => $suspensionQuery->whereNull('ended_at'))
                    ->orWhereHas('currentStable', fn ($stableQuery) => $stableQuery->whereKeyNot($this->getKey()));
            })
            ->get();

        $tagTeams = $this->previousTagTeams()
            ->where(function ($tagTeamQuery): void {
                $tagTeamQuery->whereHas('retirements', fn ($retirementQuery) => $retirementQuery->whereNull('ended_at'))
                    ->orWhereHas('suspensions', fn ($suspensionQuery) => $suspensionQuery->whereNull('ended_at'))
                    ->orWhereHas('currentStable', fn ($stableQuery) => $stableQuery->whereKeyNot($this->getKey()));
            })
            ->get();

        return $wrestlers->concat($tagTeams);
    }
}
