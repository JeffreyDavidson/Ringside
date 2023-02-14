<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\StableData;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class StableRepository
{
    /**
     * Create a new stable with the given data.
     */
    public function create(StableData $stableData): Model
    {
        return Stable::create([
            'name' => $stableData->name,
        ]);
    }

    /**
     * Update the given stable with the given data.
     */
    public function update(Stable $stable, StableData $stableData): Stable
    {
        $stable->update([
            'name' => $stableData->name,
        ]);

        return $stable;
    }

    /**
     * Delete a given stable.
     */
    public function delete(Stable $stable): void
    {
        $stable->delete();
    }

    /**
     * Restore a given stable.
     */
    public function restore(Stable $stable): void
    {
        $stable->restore();
    }

    /**
     * Activate a given stable on a given date.
     */
    public function activate(Stable $stable, Carbon $activationDate): Stable
    {
        $stable->activations()->updateOrCreate(
            ['ended_at' => null],
            ['started_at' => $activationDate->toDateTimeString()]
        );
        $stable->save();

        return $stable;
    }

    /**
     * Deactivate a given stable on a given date.
     */
    public function deactivate(Stable $stable, Carbon $deactivationDate): Stable
    {
        $stable->currentActivation()->update(['ended_at' => $deactivationDate->toDateTimeString()]);
        $stable->save();

        return $stable;
    }

    /**
     * Retire a given stable on a given date.
     */
    public function retire(Stable $stable, Carbon $retirementDate): Stable
    {
        $stable->retirements()->create(['started_at' => $retirementDate->toDateTimeString()]);
        $stable->save();

        return $stable;
    }

    /**
     * Unretire a given stable on a given date.
     */
    public function unretire(Stable $stable, Carbon $unretireDate): Stable
    {
        $stable->currentRetirement()->update(['ended_at' => $unretireDate->toDateTimeString()]);

        return $stable;
    }

    /**
     * Unretire a given stable on a given date.
     */
    public function disassemble(Stable $stable, Carbon $disassembleDate): Stable
    {
        $stable->currentWrestlers()->each(
            fn (Wrestler $wrestler) => $stable->currentWrestlers()->updateExistingPivot(
                $wrestler->id,
                ['left_at' => $disassembleDate->toDateTimeString()]
            )
        );

        $stable->currentTagTeams()->each(
            fn (TagTeam $tagTeam) => $stable->currentTagTeams()->updateExistingPivot(
                $tagTeam->id,
                ['left_at' => $disassembleDate->toDateTimeString()]
            )
        );
        $stable->save();

        return $stable;
    }

    /**
     * Add wrestlers to a given stable.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Wrestler>  $wrestlers
     */
    public function addWrestlers(Stable $stable, Collection $wrestlers, Carbon $joinDate): void
    {
        $wrestlers->each(function ($wrestler) use ($stable, $joinDate) {
            $stable->currentWrestlers()->attach($wrestler->id, ['joined_at' => $joinDate->toDateTimeString()]);
        });
    }

    /**
     * Add tag teams to a given stable at a given date.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\TagTeam>  $tagTeams
     */
    public function addTagTeams(Stable $stable, Collection $tagTeams, Carbon $joinDate): void
    {
        $tagTeams->each(function (TagTeam $tagTeam) use ($stable, $joinDate) {
            $stable->currentTagTeams()->attach($tagTeam->id, ['joined_at' => $joinDate->toDateTimeString()]);
        });
    }

    /**
     * Undocumented function.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Wrestler>  $currentWrestlers
     */
    public function removeWrestlers(Stable $stable, Collection $currentWrestlers, Carbon $removalDate): void
    {
        $currentWrestlers->each(function (Wrestler $wrestler) use ($stable, $removalDate) {
            $stable->currentWrestlers()->updateExistingPivot(
                $wrestler->id,
                ['left_at' => $removalDate->toDateTimeString()]
            );
        });
    }

    /**
     * Undocumented function.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\TagTeam>  $currentTagTeams
     */
    public function removeTagTeams(Stable $stable, Collection $currentTagTeams, Carbon $removalDate): void
    {
        $currentTagTeams->each(function (TagTeam $tagTeam) use ($stable, $removalDate) {
            $stable->currentTagTeams()->updateExistingPivot(
                $tagTeam->id,
                ['left_at' => $removalDate->toDateTimeString()]
            );
        });
    }
}
