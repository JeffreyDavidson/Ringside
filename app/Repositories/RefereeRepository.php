<?php

namespace App\Repositories;

use App\Data\RefereeData;
use App\Models\Referee;
use Carbon\Carbon;

class RefereeRepository
{
    /**
     * Create a new referee with the given data.
     *
     * @param  \App\Data\RefereeData $refereeData
     * @return \App\Models\Referee
     */
    public function create(RefereeData $refereeData)
    {
        return Referee::create([
            'first_name' => $refereeData->first_name,
            'last_name' => $refereeData->last_name,
        ]);
    }

    /**
     * Update a given referee with the given data.
     *
     * @param  \App\Models\Referee $referee
     * @param  \App\Data\RefereeData $refereeData
     * @return \App\Models\Referee
     */
    public function update(Referee $referee, RefereeData $refereeData)
    {
        $referee->update([
            'first_name' => $refereeData->first_name,
            'last_name' => $refereeData->last_name,
        ]);

        return $referee;
    }

    /**
     * Delete a given referee.
     *
     * @param  \App\Models\Referee $referee
     * @return void
     */
    public function delete(Referee $referee)
    {
        $referee->delete();
    }

    /**
     * Restore a given referee.
     *
     * @param  \App\Models\Referee $referee
     * @return void
     */
    public function restore(Referee $referee)
    {
        $referee->restore();
    }

    /**
     * Employ a given referee on a given date.
     *
     * @param  \App\Models\Referee $referee
     * @param  \Carbon\Carbon $employmentDate
     * @return \App\Models\Referee
     */
    public function employ(Referee $referee, Carbon $employmentDate)
    {
        $referee->employments()->updateOrCreate(
            ['ended_at' => null],
            ['started_at' => $employmentDate->toDateTimeString()]
        );

        return $referee;
    }

    /**
     * Release a given referee on a given date.
     *
     * @param  \App\Models\Referee $referee
     * @param  \Carbon\Carbon $releaseDate
     * @return \App\Models\Referee
     */
    public function release(Referee $referee, Carbon $releaseDate)
    {
        $referee->currentEmployment()->update(['ended_at' => $releaseDate->toDateTimeString()]);

        return $referee;
    }

    /**
     * Injure a given referee on a given date.
     *
     * @param  \App\Models\Referee $referee
     * @param  \Carbon\Carbon $injureDate
     * @return \App\Models\Referee
     */
    public function injure(Referee $referee, Carbon $injureDate)
    {
        $referee->injuries()->create(['started_at' => $injureDate->toDateTimeString()]);

        return $referee;
    }

    /**
     * Clear the current injury of a given referee on a given date.
     *
     * @param  \App\Models\Referee $referee
     * @param  \Carbon\Carbon $recoveryDate
     * @return \App\Models\Referee
     */
    public function clearInjury(Referee $referee, Carbon $recoveryDate)
    {
        $referee->currentInjury()->update(['ended_at' => $recoveryDate->toDateTimeString()]);

        return $referee;
    }

    /**
     * Retire a given referee on a given date.
     *
     * @param  \App\Models\Referee $referee
     * @param  \Carbon\Carbon $retirementDate
     * @return \App\Models\Referee
     */
    public function retire(Referee $referee, Carbon $retirementDate)
    {
        $referee->retirements()->create(['started_at' => $retirementDate->toDateTimeString()]);

        return $referee;
    }

    /**
     * Unretire a given referee on a given date.
     *
     * @param  \App\Models\Referee $referee
     * @param  \Carbon\Carbon $unretireDate
     * @return \App\Models\Referee
     */
    public function unretire(Referee $referee, Carbon $unretireDate)
    {
        $referee->currentRetirement()->update(['ended_at' => $unretireDate->toDateTimeString()]);

        return $referee;
    }

    /**
     * Suspend a given referee on a given date.
     *
     * @param  \App\Models\Referee $referee
     * @param  \Carbon\Carbon $suspensionDate
     * @return \App\Models\Referee
     */
    public function suspend(Referee $referee, Carbon $suspensionDate)
    {
        $referee->suspensions()->create(['started_at' => $suspensionDate->toDateTimeString()]);

        return $referee;
    }

    /**
     * Reinstate a given referee on a given date.
     *
     * @param  \App\Models\Referee $referee
     * @param  \Carbon\Carbon $reinstateDate
     * @return \App\Models\Referee
     */
    public function reinstate(Referee $referee, Carbon $reinstateDate)
    {
        $referee->currentSuspension()->update(['ended_at' => $reinstateDate->toDateTimeString()]);

        return $referee;
    }

    /**
     * Get the model's first employment date.
     *
     * @param  \App\Models\Referee $referee
     * @param  \Carbon\Carbon $employmentDate
     * @return \App\Models\Referee
     */
    public function updateEmployment(Referee $referee, Carbon $employmentDate)
    {
        $referee->futureEmployment()->update(['started_at' => $employmentDate->toDateTimeString()]);

        return $referee;
    }
}
