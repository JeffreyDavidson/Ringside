<?php

namespace App\Repositories;

use App\DataTransferObjects\ManagerData;
use App\Models\Manager;

class ManagerRepository
{
    /**
     * Create a new manager with the given data.
     *
     * @param  \App\DataTransferObjects\ManagerData $managerData
     * @return \App\Models\Manager
     */
    public function create(ManagerData $managerData)
    {
        return Manager::create([
            'first_name' => $managerData->first_name,
            'last_name' => $managerData->last_name,
        ]);
    }

    /**
     * Update a given manager with the given data.
     *
     * @param  \App\Models\Manager $manager
     * @param  \App\DataTransferObjects\ManagerData $managerData
     * @return \App\Models\Manager $manager
     */
    public function update(Manager $manager, ManagerData $managerData)
    {
        return $manager->update([
            'first_name' => $managerData->first_name,
            'last_name' => $managerData->last_name,
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
     * @param  string $employmentDate
     * @return \App\Models\Manager $manager
     */
    public function employ(Manager $manager, string $employmentDate)
    {
        return $manager->employments()->updateOrCreate(['ended_at' => null], ['started_at' => $employmentDate]);
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
        $manager->currentInjury()->update(['ended_at' => $recoveryDate]);

        return $manager;
    }

    /**
     * Retire a given manager on a given date.
     *
     * @param  \App\Models\Manager $manager
     * @param  string $retirementDate
     * @return \App\Models\Manager $manager
     */
    public function retire(Manager $manager, string $retirementDate)
    {
        return $manager->retirements()->create(['started_at' => $retirementDate]);
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

    /**
     * Get the model's first employment date.
     *
     * @param  \App\Models\Manager $manager
     * @param  string $employmentDate
     * @return \App\Models\Manager $manager
     */
    public function updateEmployment(Manager $manager, string $employmentDate)
    {
        return $manager->futureEmployment()->update(['started_at' => $employmentDate]);
    }

    /**
     * Updates a manager's status and saves.
     *
     * @return void
     */
    public function removeFromCurrentTagTeams($manager)
    {
        foreach ($manager->currentTagTeams as $tagTeam) {
            $manager->currentTagTeams()->updateExistingPivot($tagTeam->id, [
                'left_at' => now(),
            ]);
        }
    }

    /**
     * Updates a manager's status and saves.
     *
     * @return void
     */
    public function removeFromCurrentWrestlers($manager)
    {
        foreach ($manager->currentWrestlers as $wrestler) {
            $manager->currentWrestlers()->updateExistingPivot($wrestler->id, [
                'left_at' => now(),
            ]);
        }
    }
}
