<?php

namespace App\Repositories;

use App\DataTransferObjects\StableData;
use App\Models\Contracts\Activatable;
use App\Models\Contracts\Deactivatable;
use App\Models\Stable;
use App\Repositories\Contracts\ActivationRepositoryInterface;
use App\Repositories\Contracts\DeactivationRepositoryInterface;
use Illuminate\Support\Collection;

class StableRepository implements ActivationRepositoryInterface, DeactivationRepositoryInterface
{
    /**
     * Create a new stable with the given data.
     *
     * @param  \App\DataTransferObjects\StableData $stableData
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
     * @return \App\Models\Stable $stable
     */
    public function update(Stable $stable, StableData $stableData)
    {
        return $stable->update([
            'name' => $stableData->name,
        ]);
    }

    /**
     * Delete a given stable.
     *
     * @param  \App\Models\Stable $stable
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
     * @return void
     */
    public function restore(Stable $stable)
    {
        $stable->restore();
    }

    /**
     * Activate a given stable on a given date.
     *
     * @param  \App\Models\Contracts\Activatable $stable
     * @param  string $activationDate
     * @return \App\Models\Stable $stable
     */
    public function activate(Activatable $stable, string $activationDate)
    {
        return $stable->activations()->updateOrCreate(['ended_at' => null], ['started_at' => $activationDate]);
    }

    /**
     * Deactivate a given stable on a given date.
     *
     * @param  \App\Models\Contracts\Deactivatable $stable
     * @param  string $deactivationDate
     * @return \App\Models\Stable $stable
     */
    public function deactivate(Deactivatable $stable, string $deactivationDate)
    {
        return $stable->currentActivation()->update(['ended_at' => $deactivationDate]);
    }

    /**
     * Retire a given stable on a given date.
     *
     * @param  \App\Models\Stable $stable
     * @param  string $retirementDate
     * @return \App\Models\Stable $stable
     */
    public function retire(Stable $stable, string $retirementDate)
    {
        return $stable->retirements()->create(['started_at' => $retirementDate]);
    }

    /**
     * Unretire a given stable on a given date.
     *
     * @param  \App\Models\Stable $stable
     * @param  string $unretireDate
     * @return \App\Models\Stable $stable
     */
    public function unretire(Stable $stable, string $unretireDate)
    {
        return $stable->currentRetirement()->update(['ended_at' => $unretireDate]);
    }

    /**
     * Unretire a given stable on a given date.
     *
     * @param  \App\Models\Stable $stable
     * @param  string $unretireDate
     * @return \App\Models\Stable $stable
     */
    public function disassemble(Stable $stable, string $deactivationDate)
    {
        foreach ($stable->currentWrestlers as $wrestler) {
            $stable->currentWrestlers()->updateExistingPivot($wrestler, ['left_at' => $deactivationDate]);
            $wrestler->save();
        }

        foreach ($stable->currentTagTeams as $tagTeam) {
            $stable->currentTagTeams()->updateExistingPivot($wrestler, ['left_at' => $deactivationDate]);
            $tagTeam->save();
        }

        return $stable;
    }

    /**
     * Add wrestlers to a given stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Support\Collection  $wrestlers
     * @param  string  $joinDate
     * @return void
     */
    public function addWrestlers(Stable $stable, Collection $wrestlers, string $joinDate)
    {
        foreach ($wrestlers as $wrestler) {
            $stable->currentWrestlers()->attach($wrestler->id, ['joined_at' => $joinDate]);
        }
    }

    /**
     * Add tag teams to a given stable at a given date.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Support\Collection  $tagTeams
     * @param  string  $joinDate
     * @return void
     */
    public function addTagTeams(Stable $stable, Collection $tagTeams, string $joinDate)
    {
        foreach ($tagTeams as $tagTeam) {
            $stable->currentTagTeams()->attach($tagTeam->id, ['joined_at' => $joinDate]);
        }
    }

    /**
     * Undocumented function.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Support\Collection $currentWrestlers
     * @param  string $removalDate
     * @return void
     */
    public function removeWrestlers(Stable $stable, Collection $currentWrestlers, string $removalDate)
    {
        foreach ($currentWrestlers as $wrestler) {
            $stable->currentWrestlers()->updateExistingPivot($wrestler->id, ['left_at' => $removalDate]);
        }
    }

    /**
     * Undocumented function.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Support\Collection $currentTagTeams
     * @param  string $removalDate
     * @return void
     */
    public function removeTagTeams(Stable $stable, Collection $currentTagTeams, string $removalDate)
    {
        foreach ($currentTagTeams as $tagTeam) {
            $stable->currentTagTeams()->updateExistingPivot($tagTeam->id, ['left_at' => $removalDate]);
        }
    }
}
