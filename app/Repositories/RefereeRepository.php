<?php

namespace App\Repositories;

use App\Models\Referee;

class RefereeRepository
{
    /**
     * Create a new referee with the given data.
     *
     * @param  array $data
     * @return \App\Models\Referee
     */
    public function create(array $data)
    {
        return Referee::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
        ]);
    }

    /**
     * Update a given referee with the given data.
     *
     * @param  \App\Models\Referee $referee
     * @param  array $data
     * @return \App\Models\Referee $referee
     */
    public function update(Referee $referee, array $data)
    {
        return $referee->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
        ]);
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
     * @param  string $employmentDate
     * @return \App\Models\Referee $referee
     */
    public function employ(Referee $referee, string $employmentDate)
    {
        return $referee->employments()->updateOrCreate(['ended_at' => null], ['started_at' => $employmentDate]);
    }

    /**
     * Release a given referee on a given date.
     *
     * @param  \App\Models\Referee $referee
     * @param  string $releaseDate
     * @return \App\Models\Referee $referee
     */
    public function release(Referee $referee, string $releaseDate)
    {
        return $referee->currentEmployment()->update(['ended_at' => $releaseDate]);
    }

    /**
     * Injure a given referee on a given date.
     *
     * @param  \App\Models\Referee $referee
     * @param  string $injureDate
     * @return \App\Models\Referee $referee
     */
    public function injure(Referee $referee, string $injureDate)
    {
        return $referee->injuries()->create(['started_at' => $injureDate]);
    }

    /**
     * Clear the current injury of a given referee on a given date.
     *
     * @param  \App\Models\Referee $referee
     * @param  string $recoveryDate
     * @return \App\Models\Referee $referee
     */
    public function clearInjury(Referee $referee, string $recoveryDate)
    {
        return $referee->currentInjury()->update(['ended_at' => $recoveryDate]);
    }

    /**
     * Retire a given referee on a given date.
     *
     * @param  \App\Models\Referee $referee
     * @param  string $retirementDate
     * @return \App\Models\Referee $referee
     */
    public function retire(Referee $referee, string $retirementDate)
    {
        return $referee->retirements()->create(['started_at' => $retirementDate]);
    }

    /**
     * Unretire a given referee on a given date.
     *
     * @param  \App\Models\Referee $referee
     * @param  string $unretireDate
     * @return \App\Models\Referee $referee
     */
    public function unretire(Referee $referee, string $unretireDate)
    {
        return $referee->currentRetirement()->update(['ended_at' => $unretireDate]);
    }

    /**
     * Suspend a given referee on a given date.
     *
     * @param  \App\Models\Referee $referee
     * @param  string $suspensionDate
     * @return \App\Models\Referee $referee
     */
    public function suspend(Referee $referee, string $suspensionDate)
    {
        return $referee->suspensions()->create(['started_at' => $suspensionDate]);
    }

    /**
     * Reinstate a given referee on a given date.
     *
     * @param  \App\Models\Referee $referee
     * @param  string $reinstateDate
     * @return \App\Models\Referee $referee
     */
    public function reinstate(Referee $referee, string $reinstateDate)
    {
        return $referee->currentSuspension()->update(['ended_at' => $reinstateDate]);
    }

    /**
     * Get the model's first employment date.
     *
     * @param  \App\Models\Referee $referee
     * @param  string $employmentDate
     * @return \App\Models\Referee $referee
     */
    public function updateEmployment(Referee $referee, string $employmentDate)
    {
        return $referee->futureEmployment()->update(['started_at' => $employmentDate]);
    }
}
