<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use App\Data\StableData;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class StableRepository
{
    /**
     * Create a new stable with the given data.
     *
     * @param  \App\Data\StableData  $stableData
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(StableData $stableData): Model
    {
        return Stable::create([
            'name' => $stableData->name,
        ]);
    }

    /**
     * Update the given stable with the given data.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \App\Data\StableData  $stableData
     * @return \App\Models\Stable
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
     *
     * @param  \App\Models\Stable  $stable
     * @return void
     */
    public function delete(Stable $stable): void
    {
        $stable->delete();
    }

    /**
     * Restore a given stable.
     *
     * @param  \App\Models\Stable  $stable
     * @return void
     */
    public function restore(Stable $stable): void
    {
        $stable->restore();
    }

    /**
     * Activate a given stable on a given date.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Support\Carbon  $activationDate
     * @return \App\Models\Stable
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
     *
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Support\Carbon  $deactivationDate
     * @return \App\Models\Stable
     */
    public function deactivate(Stable $stable, Carbon $deactivationDate): Stable
    {
        $stable->currentActivation()->update(['ended_at' => $deactivationDate->toDateTimeString()]);
        $stable->save();

        return $stable;
    }

    /**
     * Retire a given stable on a given date.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Support\Carbon  $retirementDate
     * @return \App\Models\Stable
     */
    public function retire(Stable $stable, Carbon $retirementDate): Stable
    {
        $stable->retirements()->create(['started_at' => $retirementDate->toDateTimeString()]);
        $stable->save();

        return $stable;
    }

    /**
     * Unretire a given stable on a given date.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Support\Carbon  $unretireDate
     * @return \App\Models\Stable
     */
    public function unretire(Stable $stable, Carbon $unretireDate): Stable
    {
        $stable->currentRetirement()->update(['ended_at' => $unretireDate->toDateTimeString()]);

        return $stable;
    }

    /**
     * Unretire a given stable on a given date.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Support\Carbon  $disassembleDate
     * @return \App\Models\Stable
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
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Support\Collection<int, \App\Models\Wrestler>  $wrestlers
     * @param  \Illuminate\Support\Carbon  $joinDate
     * @return void
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
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Support\Collection<int, \App\Models\TagTeam>  $tagTeams
     * @param  \Illuminate\Support\Carbon  $joinDate
     * @return void
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
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Support\Collection<int, \App\Models\Wrestler>  $currentWrestlers
     * @param  \Illuminate\Support\Carbon  $removalDate
     * @return void
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
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Support\Collection<int, \App\Models\TagTeam>  $currentTagTeams
     * @param  \Illuminate\Support\Carbon  $removalDate
     * @return void
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
