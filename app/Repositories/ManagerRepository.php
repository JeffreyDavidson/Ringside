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
     */
    public function create(ManagerData $managerData): Manager
    {
        return Manager::create([
            'first_name' => $managerData->first_name,
            'last_name' => $managerData->last_name,
        ]);
    }

    /**
     * Update a given manager with the given data.
     */
    public function update(Manager $manager, ManagerData $managerData): Manager
    {
        $manager->update([
            'first_name' => $managerData->first_name,
            'last_name' => $managerData->last_name,
        ]);

        return $manager;
    }

    /**
     * Delete a given manager.
     */
    public function delete(Manager $manager): void
    {
        $manager->delete();
    }

    /**
     * Restore a given manager.
     */
    public function restore(Manager $manager): void
    {
        $manager->restore();
    }

    /**
     * Employ a given manager on a given date.
     */
    public function employ(Manager $manager, Carbon $employmentDate): Manager
    {
        $manager->employments()->updateOrCreate(
            ['ended_at' => null],
            ['started_at' => $employmentDate->toDateTimeString()]
        );

        return $manager;
    }

    /**
     * Release a given manager on a given date.
     */
    public function release(Manager $manager, Carbon $releaseDate): Manager
    {
        $manager->currentEmployment()->update(['ended_at' => $releaseDate->toDateTimeString()]);

        return $manager;
    }

    /**
     * Injure a given manager on a given date.
     */
    public function injure(Manager $manager, Carbon $injureDate): Manager
    {
        $manager->injuries()->create(['started_at' => $injureDate->toDateTimeString()]);

        return $manager;
    }

    /**
     * Clear the current injury of a given manager on a given date.
     */
    public function clearInjury(Manager $manager, Carbon $recoveryDate): Manager
    {
        $manager->currentInjury()->update(['ended_at' => $recoveryDate->toDateTimeString()]);

        return $manager;
    }

    /**
     * Retire a given manager on a given date.
     */
    public function retire(Manager $manager, Carbon $retirementDate): Manager
    {
        $manager->retirements()->create(['started_at' => $retirementDate->toDateTimeString()]);

        return $manager;
    }

    /**
     * Unretire a given manager on a given date.
     */
    public function unretire(Manager $manager, Carbon $unretireDate): Manager
    {
        $manager->currentRetirement()->update(['ended_at' => $unretireDate->toDateTimeString()]);

        return $manager;
    }

    /**
     * Suspend a given manager on a given date.
     */
    public function suspend(Manager $manager, Carbon $suspensionDate): Manager
    {
        $manager->suspensions()->create(['started_at' => $suspensionDate->toDateTimeString()]);

        return $manager;
    }

    /**
     * Reinstate a given manager on a given date.
     */
    public function reinstate(Manager $manager, Carbon $reinstateDate): Manager
    {
        $manager->currentSuspension()->update(['ended_at' => $reinstateDate->toDateTimeString()]);

        return $manager;
    }

    /**
     * Update the manager's future employment.
     */
    public function updateEmployment(Manager $manager, Carbon $employmentDate): Manager
    {
        $manager->futureEmployment()->update(['started_at' => $employmentDate->toDateTimeString()]);

        return $manager;
    }

    /**
     * Disassociate a manager from its current tag teams.
     */
    public function removeFromCurrentTagTeams(Manager $manager): void
    {
        $manager->currentTagTeams->each(function (TagTeam $tagTeam) use ($manager) {
            $manager->currentTagTeams()->updateExistingPivot($tagTeam->id, [
                'left_at' => now()->toDateTimeString(),
            ]);
        });
    }

    /**
     * Disassociate a manager from its current wrestlers.
     */
    public function removeFromCurrentWrestlers(Manager $manager): void
    {
        $manager->currentWrestlers->each(function (Wrestler $wrestler) use ($manager) {
            $manager->currentWrestlers()->updateExistingPivot($wrestler->id, [
                'left_at' => now()->toDateTimeString(),
            ]);
        });
    }
}
