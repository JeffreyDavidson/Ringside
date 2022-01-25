<?php

namespace App\Repositories;

use App\DataTransferObjects\WrestlerData;
use App\Models\Wrestler;
use Carbon\Carbon;

class WrestlerRepository
{
    /**
     * Create a new wrestler with the given data.
     *
     * @param  \App\DataTransferObjects\WrestlerData $wrestlerData
     *
     * @return \App\Models\Wrestler
     */
    public function create(WrestlerData $wrestlerData)
    {
        return Wrestler::create([
            'name' => $wrestlerData->name,
            'height' => $wrestlerData->height,
            'weight' => $wrestlerData->weight,
            'hometown' => $wrestlerData->hometown,
            'signature_move' => $wrestlerData->signature_move,
        ]);
    }

    /**
     * Update a given wrestler with given data.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  \App\DataTransferObjects\WrestlerData $wrestlerData
     *
     * @return \App\Models\Wrestler $wrestler
     */
    public function update(Wrestler $wrestler, WrestlerData $wrestlerData)
    {
        $wrestler->update([
            'name' => $wrestlerData->name,
            'height' => $wrestlerData->height,
            'weight' => $wrestlerData->weight,
            'hometown' => $wrestlerData->hometown,
            'signature_move' => $wrestlerData->signature_move,
        ]);

        return $wrestler;
    }

    /**
     * Delete a given wrestler.
     *
     * @param  \App\Models\Wrestler $wrestler
     *
     * @return void
     */
    public function delete(Wrestler $wrestler)
    {
        $wrestler->delete();
    }

    /**
     * Restore a given wrestler.
     *
     * @param  \App\Models\Wrestler $wrestler
     *
     * @return void
     */
    public function restore(Wrestler $wrestler)
    {
        $wrestler->restore();
    }

    /**
     * Employ a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  \Carbon\Carbon $employmentDate
     *
     * @return \App\Models\Wrestler $wrestler
     */
    public function employ(Wrestler $wrestler, Carbon $employmentDate)
    {
        $wrestler->employments()->updateOrCreate(
            ['ended_at' => null],
            ['started_at' => $employmentDate->toDateTimeString()]
        );

        return $wrestler;
    }

    /**
     * Release a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  \Carbon\Carbon $releaseDate
     *
     * @return \App\Models\Wrestler $wrestler
     */
    public function release(Wrestler $wrestler, Carbon $releaseDate)
    {
        $wrestler->currentEmployment()->update(['ended_at' => $releaseDate->toDateTimeString()]);

        return $wrestler;
    }

    /**
     * Injure a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  \Carbon\Carbon $injureDate
     *
     * @return \App\Models\Wrestler
     */
    public function injure(Wrestler $wrestler, Carbon $injureDate)
    {
        $wrestler->injuries()->create(['started_at' => $injureDate->toDateTimeString()]);

        return $wrestler;
    }

    /**
     * Clear the injury of a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  \Carbon\Carbon $recoveryDate
     *
     * @return \App\Models\Wrestler $wrestler
     */
    public function clearInjury(Wrestler $wrestler, Carbon $recoveryDate)
    {
        $wrestler->currentInjury()->update(['ended_at' => $recoveryDate->toDateTimeString()]);

        return $wrestler;
    }

    /**
     * Retire a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  \Carbon\Carbon $retirementDate
     *
     * @return \App\Models\Wrestler $wrestler
     */
    public function retire(Wrestler $wrestler, Carbon $retirementDate)
    {
        $wrestler->retirements()->create(['started_at' => $retirementDate->toDateTimeString()]);

        return $wrestler;
    }

    /**
     * Unretire a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  \Carbon\Carbon $unretireDate
     *
     * @return \App\Models\Wrestler $wrestler
     */
    public function unretire(Wrestler $wrestler, Carbon $unretireDate)
    {
        $wrestler->currentRetirement()->update(['ended_at' => $unretireDate->toDateTimeString()]);

        return $wrestler;
    }

    /**
     * Suspend a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  \Carbon\Carbon $suspensionDate
     *
     * @return \App\Models\Wrestler $wrestler
     */
    public function suspend(Wrestler $wrestler, Carbon $suspensionDate)
    {
        $wrestler->suspensions()->create(['started_at' => $suspensionDate->toDateTimeString()]);

        return $wrestler;
    }

    /**
     * Reinstate a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  \Carbon\Carbon $reinstateDate
     *
     * @return \App\Models\Wrestler $wrestler
     */
    public function reinstate(Wrestler $wrestler, Carbon $reinstateDate)
    {
        $wrestler->currentSuspension()->update(['ended_at' => $reinstateDate->toDateTimeString()]);

        return $wrestler;
    }

    /**
     * Get the model's first employment date.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  \Carbon\Carbon $employmentDate
     *
     * @return \App\Models\Wrestler $wrestler
     */
    public function updateEmployment(Wrestler $wrestler, Carbon $employmentDate)
    {
        $wrestler->futureEmployment()->update(['started_at' => $employmentDate->toDateTimeString()]);

        return $wrestler;
    }

    /**
     * Remove the given wrestler from their current tag team on a given date.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  \Carbon\Carbon  $removalDate
     *
     * @return void
     */
    public function removeFromCurrentTagTeam(Wrestler $wrestler, Carbon $removalDate)
    {
        $wrestler->tagTeams()->updateExistingPivot($wrestler->currentTagTeam()->id, [
            'left_at' => $removalDate->toDateTimeString(),
        ]);
    }
}
