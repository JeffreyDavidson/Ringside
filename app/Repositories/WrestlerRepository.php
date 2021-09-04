<?php

namespace App\Repositories;

use App\Models\Wrestler;

class WrestlerRepository
{
    /**
     * Create a new wrestler with the given data.
     *
     * @param  array $data
     * @return \App\Models\Wrestler
     */
    public function create($data)
    {
        return Wrestler::create([
            'name' => $data['name'],
            'height' => $data['height'],
            'weight' => $data['weight'],
            'hometown' => $data['hometown'],
            'signature_move' => $data['signature_move'],
        ]);
    }

    /**
     * Update a given wrestler with given data.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  array $data
     * @return \App\Models\Wrestler $wrestler
     */
    public function update(Wrestler $wrestler, array $data)
    {
        return $wrestler->update([
            'name' => $data['name'],
            'height' => $data['height'],
            'weight' => $data['weight'],
            'hometown' => $data['hometown'],
            'signature_move' => $data['signature_move'],
        ]);
    }

    /**
     * Employ a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  string $employmentDate
     * @return \App\Models\Wrestler $wrestler
     */
    public function employ(Wrestler $wrestler, string $employmentDate)
    {
        return $wrestler->employments()->updateOrCreate(['ended_at' => null], ['started_at' => $employmentDate]);
    }

    /**
     * Release a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  string $releaseDate
     * @return \App\Models\Wrestler $wrestler
     */
    public function release(Wrestler $wrestler, string $releaseDate)
    {
        return $wrestler->currentEmployment()->update(['ended_at' => $releaseDate]);
    }

    /**
     * Injure a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  string $injureDate
     * @return \App\Models\Wrestler
     */
    public function injure(Wrestler $wrestler, string $injureDate)
    {
        return $wrestler->injuries()->create(['started_at' => $injureDate]);
    }

    /**
     * Clear the injury of a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  string $recoveryDate
     * @return \App\Models\Wrestler $wrestler
     */
    public function clearInjury(Wrestler $wrestler, string $recoveryDate)
    {
        return $wrestler->currentInjury()->update(['ended_at' => $recoveryDate]);
    }

    /**
     * Retire a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  string $retirementDate
     * @return \App\Models\Wrestler $wrestler
     */
    public function retire(Wrestler $wrestler, string $retirementDate)
    {
        return $wrestler->retirements()->create(['started_at' => $retirementDate]);
    }

    /**
     * Unretire a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  string $unretireDate
     * @return \App\Models\Wrestler $wrestler
     */
    public function unretire(Wrestler $wrestler, string $unretireDate)
    {
        return $wrestler->currentRetirement()->update(['ended_at' => $unretireDate]);
    }

    /**
     * Suspend a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  string $suspensionDate
     * @return \App\Models\Wrestler $wrestler
     */
    public function suspend(Wrestler $wrestler, string $suspensionDate)
    {
        return $wrestler->suspensions()->create(['started_at' => $suspensionDate]);
    }

    /**
     * Reinstate a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  string $reinstateDate
     * @return \App\Models\Wrestler $wrestler
     */
    public function reinstate(Wrestler $wrestler, string $reinstateDate)
    {
        return $wrestler->currentSuspension()->update(['ended_at' => $reinstateDate]);
    }

    /**
     * Get the model's first employment date.
     *
     * @param  \App\Models\Wrestler $wrestler
     * @param  string $employmentDate
     * @return \App\Models\Wrestler $wrestler
     */
    public function updateEmployment(Wrestler $wrestler, string $employmentDate)
    {
        return $wrestler->futureEmployment()->update(['started_at' => $employmentDate]);
    }

    /**
     * Remove the given wrestler from their current tag team on a given date.
     *
     * @param  \App\Models\Wrestler
     * @param  string  $removalDate
     * @return void
     */
    public function removeFromCurrentTagTeam(Wrestler $wrestler, string $removalDate)
    {
        $wrestler->currentTagTeam()->updateExistingPivot($wrestler->currentTagTeam->id, [
            'left_at' => $removalDate,
        ]);
    }

    /**
     * Retrieve the model instance by the id field.
     *
     * @param  int $wrestlerId
     * @return \App\Models\Wrestler
     */
    public function findById(int $wrestlerId)
    {
        return Wrestler::find($wrestlerId);
    }
}
