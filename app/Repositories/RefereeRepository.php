<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use App\Data\RefereeData;
use App\Models\Referee;
use Illuminate\Support\Carbon;

class RefereeRepository
{
    /**
     * Create a new referee with the given data.
     *
     * @param  \App\Data\RefereeData  $refereeData
     * @return \Illuminate\Database\Eloquent\Model
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
     *
     * @param  \App\Models\Referee  $referee
     * @param  \App\Data\RefereeData  $refereeData
     * @return \App\Models\Referee
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
     *
     * @param  \App\Models\Referee  $referee
     * @return void
     */
    public function delete(Referee $referee): void
    {
        $referee->delete();
    }

    /**
     * Restore a given referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return void
     */
    public function restore(Referee $referee): void
    {
        $referee->restore();
    }

    /**
     * Employ a given referee on a given date.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \Illuminate\Support\Carbon  $employmentDate
     * @return \App\Models\Referee
     */
    public function employ(Referee $referee, Carbon $employmentDate): Referee
    {
        $referee->employments()->updateOrCreate(
            ['ended_at' => null],
            ['started_at' => $employmentDate->toDateTimeString()]
        );
        $referee->save();

        return $referee;
    }

    /**
     * Release a given referee on a given date.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \Illuminate\Support\Carbon  $releaseDate
     * @return \App\Models\Referee
     */
    public function release(Referee $referee, Carbon $releaseDate): Referee
    {
        $referee->currentEmployment()->update(['ended_at' => $releaseDate->toDateTimeString()]);
        $referee->save();

        return $referee;
    }

    /**
     * Injure a given referee on a given date.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \Illuminate\Support\Carbon  $injureDate
     * @return \App\Models\Referee
     */
    public function injure(Referee $referee, Carbon $injureDate): Referee
    {
        $referee->injuries()->create(['started_at' => $injureDate->toDateTimeString()]);
        $referee->save();

        return $referee;
    }

    /**
     * Clear the current injury of a given referee on a given date.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \Illuminate\Support\Carbon  $recoveryDate
     * @return \App\Models\Referee
     */
    public function clearInjury(Referee $referee, Carbon $recoveryDate): Referee
    {
        $referee->currentInjury()->update(['ended_at' => $recoveryDate->toDateTimeString()]);
        $referee->save();

        return $referee;
    }

    /**
     * Retire a given referee on a given date.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \Illuminate\Support\Carbon  $retirementDate
     * @return \App\Models\Referee
     */
    public function retire(Referee $referee, Carbon $retirementDate): Referee
    {
        $referee->retirements()->create(['started_at' => $retirementDate->toDateTimeString()]);
        $referee->save();

        return $referee;
    }

    /**
     * Unretire a given referee on a given date.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \Illuminate\Support\Carbon  $unretireDate
     * @return \App\Models\Referee
     */
    public function unretire(Referee $referee, Carbon $unretireDate): Referee
    {
        $referee->currentRetirement()->update(['ended_at' => $unretireDate->toDateTimeString()]);
        $referee->save();

        return $referee;
    }

    /**
     * Suspend a given referee on a given date.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \Illuminate\Support\Carbon  $suspensionDate
     * @return \App\Models\Referee
     */
    public function suspend(Referee $referee, Carbon $suspensionDate): Referee
    {
        $referee->suspensions()->create(['started_at' => $suspensionDate->toDateTimeString()]);
        $referee->save();

        return $referee;
    }

    /**
     * Reinstate a given referee on a given date.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \Illuminate\Support\Carbon  $reinstateDate
     * @return \App\Models\Referee
     */
    public function reinstate(Referee $referee, Carbon $reinstateDate): Referee
    {
        $referee->currentSuspension()->update(['ended_at' => $reinstateDate->toDateTimeString()]);
        $referee->save();

        return $referee;
    }

    /**
     * Get the model's first employment date.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \Illuminate\Support\Carbon  $employmentDate
     * @return \App\Models\Referee
     */
    public function updateEmployment(Referee $referee, Carbon $employmentDate): Referee
    {
        $referee->futureEmployment()->update(['started_at' => $employmentDate->toDateTimeString()]);

        return $referee;
    }
}
