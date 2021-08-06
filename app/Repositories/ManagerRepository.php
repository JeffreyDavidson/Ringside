<?php

namespace App\Repositories;

use App\Models\Manager;

class ManagerRepository
{
    /**
     * Create a new manager with the given data.
     *
     * @param  array $data
     * @return \App\Models\Manager
     */
    public function create(array $data)
    {
        return Manager::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
        ]);
    }

    /**
     * Update a given manager with the given data.
     *
     * @param  \App\Models\Manager $manager
     * @param  array $data
     * @return \App\Models\Manager $manager
     */
    public function update(Manager $manager, array $data)
    {
        return $manager->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
        ]);
    }

    /**
     * Delete a given manager.
     *
     * @param  \App\Models\Manager $manager
     * @return void
     */
    public function delete(Manager $manager)
    {
        $manager->delete();
    }

    /**
     * Restore a given manager.
     *
     * @param  \App\Models\Manager $manager
     * @return void
     */
    public function restore(Manager $manager)
    {
        $manager->restore();
    }

    /**
     * Employ a given manager on a given date.
     *
     * @param  \App\Models\Manager $manager
     * @param  string $startDate
     * @return \App\Models\Manager $manager
     */
    public function employ(Manager $manager, string $startDate)
    {
        return $manager->employments()->updateOrCreate(['ended_at' => null], ['started_at' => $startDate]);
    }

    /**
     * Release a given manager on a given date.
     *
     * @param  \App\Models\Manager $manager
     * @param  string $releaseDate
     * @return \App\Models\Manager $manager
     */
    public function release(Manager $manager, string $releaseDate)
    {
        return $manager->currentEmployment()->update(['ended_at' => $releaseDate]);
    }

    /**
     * Injure a given manager on a given date.
     *
     * @param  \App\Models\Manager $manager
     * @param  string $injureDate
     * @return \App\Models\Manager $manager
     */
    public function injure(Manager $manager, string $injureDate)
    {
        return $manager->injuries()->create(['started_at' => $injureDate]);
    }

    /**
     * Clear the current injury of a given manager on a given date.
     *
     * @param  \App\Models\Manager $manager
     * @param  string $recoveryDate
     * @return \App\Models\Manager $manager
     */
    public function clearInjury(Manager $manager, string $recoveryDate)
    {
        return $manager->currentInjury()->update(['ended_at' => $recoveryDate]);
    }

    /**
     * Retire a given manager on a given date.
     *
     * @param  \App\Models\Manager $manager
     * @param  string $retireDate
     * @return \App\Models\Manager $manager
     */
    public function retire(Manager $manager, string $retireDate)
    {
        return $manager->retirements()->create(['started_at' => $retireDate]);
    }

    /**
     * Unretire a given manager on a given date.
     *
     * @param  \App\Models\Manager $manager
     * @param  string $unretireDate
     * @return \App\Models\Manager $manager
     */
    public function unretire(Manager $manager, string $unretireDate)
    {
        return $manager->currentRetirement()->update(['ended_at' => $unretireDate]);
    }

    /**
     * Suspend a given manager on a given date.
     *
     * @param  \App\Models\Manager $manager
     * @param  string $suspensionDate
     * @return \App\Models\Manager $manager
     */
    public function suspend(Manager $manager, string $suspensionDate)
    {
        return $manager->suspensions()->create(['started_at' => $suspensionDate]);
    }

    /**
     * Reinstate a given manager on a given date.
     *
     * @param  \App\Models\Manager $manager
     * @param  string $reinstateDate
     * @return \App\Models\Manager $manager
     */
    public function reinstate(Manager $manager, string $reinstateDate)
    {
        return $manager->currentSuspension()->update(['ended_at' => $reinstateDate]);
    }
}
