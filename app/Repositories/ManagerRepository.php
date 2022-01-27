<?php

namespace App\Repositories;

use App\DataTransferObjects\ManagerData;
use App\Models\Manager;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;

class ManagerRepository
{
    /**
     * Create a new manager with the given data.
     *
     * @param  \App\DataTransferObjects\ManagerData $managerData
     *
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
     *
     * @return \App\Models\Manager
     */
    public function update(Manager $manager, ManagerData $managerData)
    {
        $manager->update([
            'first_name' => $managerData->first_name,
            'last_name' => $managerData->last_name,
        ]);

        return $manager;
    }

    /**
     * Delete a given manager.
     *
     * @param  \App\Models\Manager $manager
     *
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
     *
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
     * @param  \Carbon\Carbon $employmentDate
     *
     * @return \App\Models\Manager
     */
    public function employ(Manager $manager, Carbon $employmentDate)
    {
        $manager->employments()->updateOrCreate(
            ['ended_at' => null],
            ['started_at' => $employmentDate->toDateTimeString()]
        );

        return $manager;
    }

    /**
     * Release a given manager on a given date.
     *
     * @param  \App\Models\Manager $manager
     * @param  \Carbon\Carbon $releaseDate
     *
     * @return \App\Models\Manager
     */
    public function release(Manager $manager, Carbon $releaseDate)
    {
        $manager->currentEmployment()->update(['ended_at' => $releaseDate->toDateTimeString()]);

        return $manager;
    }

    /**
     * Injure a given manager on a given date.
     *
     * @param  \App\Models\Manager $manager
     * @param  \Carbon\Carbon $injureDate
     *
     * @return \App\Models\Manager
     */
    public function injure(Manager $manager, Carbon $injureDate)
    {
        $manager->injuries()->create(['started_at' => $injureDate->toDateTimeString()]);

        return $manager;
    }

    /**
     * Clear the current injury of a given manager on a given date.
     *
     * @param  \App\Models\Manager $manager
     * @param  \Carbon\Carbon $recoveryDate
     *
     * @return \App\Models\Manager
     */
    public function clearInjury(Manager $manager, Carbon $recoveryDate)
    {
        $manager->currentInjury()->update(['ended_at' => $recoveryDate->toDateTimeString()]);

        return $manager;
    }

    /**
     * Retire a given manager on a given date.
     *
     * @param  \App\Models\Manager $manager
     * @param  \Carbon\Carbon $retirementDate
     *
     * @return \App\Models\Manager
     */
    public function retire(Manager $manager, Carbon $retirementDate)
    {
        $manager->retirements()->create(['started_at' => $retirementDate->toDateTimeString()]);

        return $manager;
    }

    /**
     * Unretire a given manager on a given date.
     *
     * @param  \App\Models\Manager $manager
     * @param  \Carbon\Carbon $unretireDate
     *
     * @return \App\Models\Manager
     */
    public function unretire(Manager $manager, Carbon $unretireDate)
    {
        $manager->currentRetirement()->update(['ended_at' => $unretireDate->toDateTimeString()]);

        return $manager;
    }

    /**
     * Suspend a given manager on a given date.
     *
     * @param  \App\Models\Manager $manager
     * @param  \Carbon\Carbon $suspensionDate
     *
     * @return \App\Models\Manager
     */
    public function suspend(Manager $manager, Carbon $suspensionDate)
    {
        $manager->suspensions()->create(['started_at' => $suspensionDate->toDateTimeString()]);

        return $manager;
    }

    /**
     * Reinstate a given manager on a given date.
     *
     * @param  \App\Models\Manager $manager
     * @param  \Carbon\Carbon $reinstateDate
     *
     * @return \App\Models\Manager
     */
    public function reinstate(Manager $manager, Carbon $reinstateDate)
    {
        $manager->currentSuspension()->update(['ended_at' => $reinstateDate->toDateTimeString()]);

        return $manager;
    }

    /**
     * Get the model's first employment date.
     *
     * @param  \App\Models\Manager $manager
     * @param  \Carbon\Carbon $employmentDate
     *
     * @return \App\Models\Manager
     */
    public function updateEmployment(Manager $manager, Carbon $employmentDate)
    {
        $manager->futureEmployment()->update(['started_at' => $employmentDate->toDateTimeString()]);

        return $manager;
    }

    /**
     * Updates a manager's status and saves.
     *
     * @param  \App\Models\Manager $manager
     *
     * @return void
     */
    public function removeFromCurrentTagTeams(Manager $manager)
    {
        $manager->currentTagTeams->each(function (TagTeam $tagTeam) use ($manager) {
            $manager->currentTagTeams()->updateExistingPivot($tagTeam->id, [
                'left_at' => now()->toDateTimeString(),
            ]);
        });
    }

    /**
     * Updates a manager's status and saves.
     *
     * @param  \App\Models\Manager $manager
     *
     * @return void
     */
    public function removeFromCurrentWrestlers(Manager $manager)
    {
        $manager->currentWrestlers->each(function (Wrestler $wrestler) use ($manager) {
            $manager->currentWrestlers()->updateExistingPivot($wrestler->id, [
                'left_at' => now()->toDateTimeString(),
            ]);
        });
    }
}
