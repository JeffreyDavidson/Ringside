<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\ManagerData;
use App\Models\Manager;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;

class ManagerRepository
{
    /**
     * Create a new manager with the given data.
     *
     * @param  \App\Data\ManagerData  $managerData
     * @return \Illuminate\Database\Eloquent\Model
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
     * @param  \App\Models\Manager  $manager
     * @param  \App\Data\ManagerData  $managerData
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
     * @param  \App\Models\Manager  $manager
     * @return void
     */
    public function delete(Manager $manager)
    {
        $manager->delete();
    }

    /**
     * Restore a given manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return void
     */
    public function restore(Manager $manager)
    {
        $manager->restore();
    }

    /**
     * Employ a given manager on a given date.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \Illuminate\Support\Carbon  $employmentDate
     * @return \App\Models\Manager
     */
    public function employ(Manager $manager, Carbon $employmentDate)
    {
        $manager->employments()->updateOrCreate(
            ['ended_at' => null],
            ['started_at' => $employmentDate->toDateTimeString()]
        );
        $manager->save();

        return $manager;
    }

    /**
     * Release a given manager on a given date.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \Illuminate\Support\Carbon  $releaseDate
     * @return \App\Models\Manager
     */
    public function release(Manager $manager, Carbon $releaseDate)
    {
        $manager->currentEmployment()->update(['ended_at' => $releaseDate->toDateTimeString()]);
        $manager->save();

        return $manager;
    }

    /**
     * Injure a given manager on a given date.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \Illuminate\Support\Carbon  $injureDate
     * @return \App\Models\Manager
     */
    public function injure(Manager $manager, Carbon $injureDate)
    {
        $manager->injuries()->create(['started_at' => $injureDate->toDateTimeString()]);
        $manager->save();

        return $manager;
    }

    /**
     * Clear the current injury of a given manager on a given date.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \Illuminate\Support\Carbon  $recoveryDate
     * @return \App\Models\Manager
     */
    public function clearInjury(Manager $manager, Carbon $recoveryDate)
    {
        $manager->currentInjury()->update(['ended_at' => $recoveryDate->toDateTimeString()]);
        $manager->save();

        return $manager;
    }

    /**
     * Retire a given manager on a given date.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \Illuminate\Support\Carbon  $retirementDate
     * @return \App\Models\Manager
     */
    public function retire(Manager $manager, Carbon $retirementDate)
    {
        $manager->retirements()->create(['started_at' => $retirementDate->toDateTimeString()]);
        $manager->save();

        return $manager;
    }

    /**
     * Unretire a given manager on a given date.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \Illuminate\Support\Carbon  $unretireDate
     * @return \App\Models\Manager
     */
    public function unretire(Manager $manager, Carbon $unretireDate)
    {
        $manager->currentRetirement()->update(['ended_at' => $unretireDate->toDateTimeString()]);
        $manager->save();

        return $manager;
    }

    /**
     * Suspend a given manager on a given date.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \Illuminate\Support\Carbon  $suspensionDate
     * @return \App\Models\Manager
     */
    public function suspend(Manager $manager, Carbon $suspensionDate)
    {
        $manager->suspensions()->create(['started_at' => $suspensionDate->toDateTimeString()]);
        $manager->save();

        return $manager;
    }

    /**
     * Reinstate a given manager on a given date.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \Illuminate\Support\Carbon  $reinstateDate
     * @return \App\Models\Manager
     */
    public function reinstate(Manager $manager, Carbon $reinstateDate)
    {
        $manager->currentSuspension()->update(['ended_at' => $reinstateDate->toDateTimeString()]);
        $manager->save();

        return $manager;
    }

    /**
     * Get the model's first employment date.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \Illuminate\Support\Carbon  $employmentDate
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
     * @param  \App\Models\Manager  $manager
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
     * @param  \App\Models\Manager  $manager
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
