<?php

namespace App\Repositories;

use App\DataTransferObjects\StableData;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StableRepository
{
    /**
     * Create a new stable with the given data.
     *
     * @param  \App\DataTransferObjects\StableData $stableData
     *
     * @return \App\Models\Stable
     */
    public function create(StableData $stableData)
    {
        return Stable::create([
            'name' => $stableData->name,
        ]);
    }

    /**
     * Update the given stable with the given data.
     *
     * @param  \App\Models\Stable $stable
     * @param  \App\DataTransferObjects\StableData $stableData
     *
     * @return \App\Models\Stable
     */
    public function update(Stable $stable, StableData $stableData)
    {
        $stable->update([
            'name' => $stableData->name,
        ]);

        return $stable;
    }

    /**
     * Delete a given stable.
     *
     * @param  \App\Models\Stable $stable
     *
     * @return void
     */
    public function delete(Stable $stable)
    {
        $stable->delete();
    }

    /**
     * Restore a given stable.
     *
     * @param  \App\Models\Stable $stable
     *
     * @return void
     */
    public function restore(Stable $stable)
    {
        $stable->restore();
    }

    /**
     * Activate a given stable on a given date.
     *
     * @param  \App\Models\Stable $stable
     * @param  \Carbon\Carbon $activationDate
     *
     * @return \App\Models\Stable
     */
    public function activate(Stable $stable, Carbon $activationDate)
    {
        $stable->activations()->updateOrCreate(
            ['ended_at' => null],
            ['started_at' => $activationDate->toDateTimeString()]
        );

        return $stable;
    }

    /**
     * Deactivate a given stable on a given date.
     *
     * @param  \App\Models\Stable $stable
     * @param  \Carbon\Carbon $deactivationDate
     *
     * @return \App\Models\Stable
     */
    public function deactivate(Stable $stable, Carbon $deactivationDate)
    {
        $stable->currentActivation()->update(['ended_at' => $deactivationDate->toDateTimeString()]);

        return $stable;
    }

    /**
     * Retire a given stable on a given date.
     *
     * @param  \App\Models\Stable $stable
     * @param  \Carbon\Carbon $retirementDate
     *
     * @return \App\Models\Stable
     */
    public function retire(Stable $stable, Carbon $retirementDate)
    {
        $stable->retirements()->create(['started_at' => $retirementDate->toDateTimeString()]);

        return $stable;
    }

    /**
     * Unretire a given stable on a given date.
     *
     * @param  \App\Models\Stable $stable
     * @param  \Carbon\Carbon $unretireDate
     *
     * @return \App\Models\Stable
     */
    public function unretire(Stable $stable, Carbon $unretireDate)
    {
        $stable->currentRetirement()->update(['ended_at' => $unretireDate->toDateTimeString()]);

        return $stable;
    }

    /**
     * Unretire a given stable on a given date.
     *
     * @param  \App\Models\Stable $stable
     * @param  \Carbon\Carbon $disassembleDate
     *
     * @return \App\Models\Stable
     */
    public function disassemble(Stable $stable, Carbon $disassembleDate)
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

        return $stable;
    }

    /**
     * Add wrestlers to a given stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Support\Collection  $wrestlers
     * @param  \Carbon\Carbon  $joinDate
     *
     * @return void
     */
    public function addWrestlers(Stable $stable, Collection $wrestlers, Carbon $joinDate)
    {
        $wrestlers->each(function ($wrestler) use ($stable, $joinDate) {
            $stable->currentWrestlers()->attach($wrestler->id, ['joined_at' => $joinDate->toDateTimeString()]);
        });
    }

    /**
     * Add tag teams to a given stable at a given date.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Support\Collection  $tagTeams
     * @param  \Carbon\Carbon  $joinDate
     *
     * @return void
     */
    public function addTagTeams(Stable $stable, Collection $tagTeams, Carbon $joinDate)
    {
        $tagTeams->each(function (TagTeam $tagTeam) use ($stable, $joinDate) {
            $stable->currentTagTeams()->attach($tagTeam->id, ['joined_at' => $joinDate->toDateTimeString()]);
        });
    }

    /**
     * Undocumented function.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Support\Collection $currentWrestlers
     * @param  \Carbon\Carbon $removalDate
     *
     * @return void
     */
    public function removeWrestlers(Stable $stable, Collection $currentWrestlers, Carbon $removalDate)
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
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Support\Collection $currentTagTeams
     * @param  \Carbon\Carbon $removalDate
     *
     * @return void
     */
    public function removeTagTeams(Stable $stable, Collection $currentTagTeams, Carbon $removalDate)
    {
        $currentTagTeams->each(function (TagTeam $tagTeam) use ($stable, $removalDate) {
            $stable->currentTagTeams()->updateExistingPivot(
                $tagTeam->id,
                ['left_at' => $removalDate->toDateTimeString()]
            );
        });
    }
}
