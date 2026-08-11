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
            ->whereHas('employments', fn ($query) => $query->whereNull('ended_at'))
            ->whereDoesntHave('injuries', fn ($query) => $query->whereNull('ended_at'))
            ->whereDoesntHave('suspensions', fn ($query) => $query->whereNull('ended_at'))
            ->get();

        $tagTeams = $this->previousTagTeams()
            ->whereHas('employments', fn ($query) => $query->whereNull('ended_at'))
            ->whereDoesntHave('suspensions', fn ($query) => $query->whereNull('ended_at'))
            ->get();

        return $wrestlers->concat($tagTeams);
    }

    /** @return Collection<int, Wrestler|TagTeam> */
    public function getUnavailableKeyFormerMembers(): Collection
    {
        $wrestlers = $this->previousWrestlers()
            ->where(function ($query): void {
                $query->whereHas('retirements', fn ($query) => $query->whereNull('ended_at'))
                    ->orWhereHas('injuries', fn ($query) => $query->whereNull('ended_at'))
                    ->orWhereHas('suspensions', fn ($query) => $query->whereNull('ended_at'))
                    ->orWhereHas('currentStable', fn ($query) => $query->whereKeyNot($this->getKey()));
            })
            ->get();

        $tagTeams = $this->previousTagTeams()
            ->where(function ($query): void {
                $query->whereHas('retirements', fn ($query) => $query->whereNull('ended_at'))
                    ->orWhereHas('suspensions', fn ($query) => $query->whereNull('ended_at'))
                    ->orWhereHas('currentStable', fn ($query) => $query->whereKeyNot($this->getKey()));
            })
            ->get();

        return $wrestlers->concat($tagTeams);
    }
}
