<?php

namespace App\Repositories;

use App\Models\Contracts\Activatable;
use App\Models\Contracts\Deactivatable;
use App\Models\Stable;
use App\Repositories\Contracts\ActivationRepositoryInterface;
use App\Repositories\Contracts\DeactivationRepositoryInterface;

class StableRepository implements ActivationRepositoryInterface, DeactivationRepositoryInterface
{
    /**
     * Create a new stable with the given data.
     *
     * @param  array $data
     * @return \App\Models\Stable
     */
    public function create(array $data)
    {
        return Stable::create([
            'name' => $data['name'],
        ]);
    }

    /**
     * Update the given stable with the given data.
     *
     * @param  \App\Models\Stable $stable
     * @param  array $data
     * @return \App\Models\Stable $stable
     */
    public function update(Stable $stable, array $data)
    {
        return $stable->update([
            'name' => $data['name'],
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
            $wrestler->updateStatus()->save();
        }

        foreach ($stable->currentTagTeams as $tagTeam) {
            $stable->currentTagTeams()->updateExistingPivot($wrestler, ['left_at' => $deactivationDate]);
            $tagTeam->updateStatus()->save();
        }

        return $stable;
    }

    /**
     * Add wrestlers to a given stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  array  $wrestlerIds
     * @param  string  $joinDate
     * @return void
     */
    public function addWrestlers(Stable $stable, array $wrestlerIds, string $joinDate)
    {
        foreach ($wrestlerIds as $wrestlerId) {
            $stable->currentWrestlers()->attach($wrestlerId, ['joined_at' => $joinDate]);
        }
    }

    /**
     * Add tag teams to a given stable at a given date.
     *
     * @param  \App\Models\Stable  $stable
     * @param  array  $tagTeamIds
     * @param  string  $joinDate
     * @return void
     */
    public function addTagTeams(Stable $stable, array $tagTeamIds, string $joinDate)
    {
        foreach ($tagTeamIds as $tagTeamId) {
            $stable->currentTagTeams()->attach($tagTeamId, ['joined_at' => $joinDate]);
        }
    }

    /**
     * Undocumented function.
     *
     * @param  \App\Models\Stable  $stable
     * @param  array $currentWrestlerIds
     * @param  string $removalDate
     * @return void
     */
    public function removeWrestlers(Stable $stable, array $currentWrestlerIds, string $removalDate)
    {
        foreach ($currentWrestlerIds as $wrestlerId) {
            $stable->currentWrestlers()->updateExistingPivot($wrestlerId, ['left_at' => $removalDate]);
        }
    }

    /**
     * Undocumented function.
     *
     * @param  \App\Models\Stable  $stable
     * @param  array $currentTagTeamIds
     * @param  string $removalDate
     * @return void
     */
    public function removeTagTeams(Stable $stable, array $currentTagTeamIds, string $removalDate)
    {
        foreach ($currentTagTeamIds as $tagTeamId) {
            $stable->currentTagTeams()->updateExistingPivot($tagTeamId, ['left_at' => $removalDate]);
        }
    }
}
