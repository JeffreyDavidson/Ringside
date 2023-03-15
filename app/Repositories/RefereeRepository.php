<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\RefereeData;
use App\Models\Referee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class RefereeRepository
{
    /**
     * Create a new referee with the given data.
     */
    public function create(RefereeData $refereeData): Model
    {
        return Referee::create([
            'first_name' => $refereeData->first_name,
            'last_name' => $refereeData->last_name,
        ]);
    }

    /**
     * Update a given referee with the given data.
     */
    public function update(Referee $referee, RefereeData $refereeData): Referee
    {
        $referee->update([
            'first_name' => $refereeData->first_name,
            'last_name' => $refereeData->last_name,
        ]);

        return $referee;
    }

    /**
     * Delete a given referee.
     */
    public function delete(Referee $referee): void
    {
        $referee->delete();
    }

    /**
     * Restore a given referee.
     */
    public function restore(Referee $referee): void
    {
        $referee->restore();
    }

    /**
     * Employ a given referee on a given date.
     */
    public function employ(Referee $referee, Carbon $employmentDate): Referee
    {
        $referee->employments()->updateOrCreate(
            ['ended_at' => null],
            ['started_at' => $employmentDate->toDateTimeString()]
        );

        return $referee;
    }

    /**
     * Release a given referee on a given date.
     */
    public function release(Referee $referee, Carbon $releaseDate): Referee
    {
        $referee->currentEmployment()->update(['ended_at' => $releaseDate->toDateTimeString()]);

        return $referee;
    }

    /**
     * Injure a given referee on a given date.
     */
    public function injure(Referee $referee, Carbon $injureDate): Referee
    {
        $referee->injuries()->create(['started_at' => $injureDate->toDateTimeString()]);

        return $referee;
    }

    /**
     * Clear the current injury of a given referee on a given date.
     */
    public function clearInjury(Referee $referee, Carbon $recoveryDate): Referee
    {
        $referee->currentInjury()->update(['ended_at' => $recoveryDate->toDateTimeString()]);

        return $referee;
    }

    /**
     * Retire a given referee on a given date.
     */
    public function retire(Referee $referee, Carbon $retirementDate): Referee
    {
        $referee->retirements()->create(['started_at' => $retirementDate->toDateTimeString()]);

        return $referee;
    }

    /**
     * Unretire a given referee on a given date.
     */
    public function unretire(Referee $referee, Carbon $unretireDate): Referee
    {
        $referee->currentRetirement()->update(['ended_at' => $unretireDate->toDateTimeString()]);

        return $referee;
    }

    /**
     * Suspend a given referee on a given date.
     */
    public function suspend(Referee $referee, Carbon $suspensionDate): Referee
    {
        $referee->suspensions()->create(['started_at' => $suspensionDate->toDateTimeString()]);

        return $referee;
    }

    /**
     * Reinstate a given referee on a given date.
     */
    public function reinstate(Referee $referee, Carbon $reinstateDate): Referee
    {
        $referee->currentSuspension()->update(['ended_at' => $reinstateDate->toDateTimeString()]);

        return $referee;
    }

    /**
     * Get the model's first employment date.
     */
    public function updateEmployment(Referee $referee, Carbon $employmentDate): Referee
    {
        $referee->futureEmployment()->update(['started_at' => $employmentDate->toDateTimeString()]);

        return $referee;
    }
}
