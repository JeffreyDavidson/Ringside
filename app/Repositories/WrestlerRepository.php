<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\WrestlerData;
use App\Enums\WrestlerStatus;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;

class WrestlerRepository
{
    /**
     * Create a new wrestler with the given data.
     *
     * @param  \App\Data\WrestlerData  $wrestlerData
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(WrestlerData $wrestlerData)
    {
        return Wrestler::create([
            'name' => $wrestlerData->name,
            'height' => $wrestlerData->height,
            'weight' => $wrestlerData->weight,
            'hometown' => $wrestlerData->hometown,
            'signature_move' => $wrestlerData->signature_move,
        ]);
    }

    /**
     * Update a given wrestler with given data.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Data\WrestlerData  $wrestlerData
     * @return \App\Models\Wrestler
     */
    public function update(Wrestler $wrestler, WrestlerData $wrestlerData)
    {
        $wrestler->update([
            'name' => $wrestlerData->name,
            'height' => $wrestlerData->height,
            'weight' => $wrestlerData->weight,
            'hometown' => $wrestlerData->hometown,
            'signature_move' => $wrestlerData->signature_move,
        ]);

        return $wrestler;
    }

    /**
     * Delete a given wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @return void
     */
    public function delete(Wrestler $wrestler)
    {
        $wrestler->delete();
    }

    /**
     * Restore a given wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @return void
     */
    public function restore(Wrestler $wrestler)
    {
        $wrestler->restore();
    }

    /**
     * Employ a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \Illuminate\Support\Carbon  $employmentDate
     * @return \App\Models\Wrestler
     */
    public function employ(Wrestler $wrestler, Carbon $employmentDate)
    {
        $wrestler->employments()->updateOrCreate(
            ['ended_at' => null],
            ['started_at' => $employmentDate->toDateTimeString()]
        );
        $wrestler->save();

        return $wrestler;
    }

    /**
     * Release a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \Illuminate\Support\Carbon  $releaseDate
     * @return \App\Models\Wrestler
     */
    public function release(Wrestler $wrestler, Carbon $releaseDate)
    {
        $wrestler->currentEmployment()->update(['ended_at' => $releaseDate->toDateTimeString()]);
        $wrestler->save();

        return $wrestler;
    }

    /**
     * Injure a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \Illuminate\Support\Carbon  $injureDate
     * @return \App\Models\Wrestler
     */
    public function injure(Wrestler $wrestler, Carbon $injureDate)
    {
        $wrestler->injuries()->create(['started_at' => $injureDate->toDateTimeString()]);
        $wrestler->save();

        return $wrestler;
    }

    /**
     * Clear the injury of a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \Illuminate\Support\Carbon  $recoveryDate
     * @return \App\Models\Wrestler
     */
    public function clearInjury(Wrestler $wrestler, Carbon $recoveryDate)
    {
        $wrestler->currentInjury()->update(['ended_at' => $recoveryDate->toDateTimeString()]);
        $wrestler->save();

        return $wrestler;
    }

    /**
     * Retire a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \Illuminate\Support\Carbon  $retirementDate
     * @return \App\Models\Wrestler
     */
    public function retire(Wrestler $wrestler, Carbon $retirementDate)
    {
        $wrestler->retirements()->create(['started_at' => $retirementDate->toDateTimeString()]);
        $wrestler->save();

        return $wrestler;
    }

    /**
     * Unretire a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \Illuminate\Support\Carbon  $unretireDate
     * @return \App\Models\Wrestler
     */
    public function unretire(Wrestler $wrestler, Carbon $unretireDate)
    {
        $wrestler->currentRetirement()->update(['ended_at' => $unretireDate->toDateTimeString()]);
        $wrestler->save();

        return $wrestler;
    }

    /**
     * Suspend a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \Illuminate\Support\Carbon  $suspensionDate
     * @return \App\Models\Wrestler
     */
    public function suspend(Wrestler $wrestler, Carbon $suspensionDate)
    {
        $wrestler->suspensions()->create(['started_at' => $suspensionDate->toDateTimeString()]);
        $wrestler->save();

        return $wrestler;
    }

    /**
     * Reinstate a given wrestler on a given date.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \Illuminate\Support\Carbon  $reinstateDate
     * @return \App\Models\Wrestler
     */
    public function reinstate(Wrestler $wrestler, Carbon $reinstateDate)
    {
        $wrestler->currentSuspension()->update(['ended_at' => $reinstateDate->toDateTimeString()]);
        $wrestler->save();

        return $wrestler;
    }

    /**
     * Get the model's first employment date.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \Illuminate\Support\Carbon  $employmentDate
     * @return \App\Models\Wrestler
     */
    public function updateEmployment(Wrestler $wrestler, Carbon $employmentDate)
    {
        $wrestler->futureEmployment()->update(['started_at' => $employmentDate->toDateTimeString()]);

        return $wrestler;
    }

    /**
     * Remove the given wrestler from their current tag team on a given date.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \Illuminate\Support\Carbon  $removalDate
     * @return void
     */
    public function removeFromCurrentTagTeam(Wrestler $wrestler, Carbon $removalDate)
    {
        $wrestler->update(['current_tag_team_id' => null]);

        $wrestler->tagTeams()->wherePivotNull('left_at')->updateExistingPivot($wrestler->currentTagTeam->id, [
            'left_at' => $removalDate->toDateTimeString(),
        ]);
    }

    /**
     * Undocumented function
     *
     * @return \App\Builders\WrestlerQueryBuilder
     */
    public static function getAvailableWrestlersForNewTagTeam()
    {
        // Each wrestler must be either:
        // have a currentEmployment (scope called employed)
        // AND have a status of bookable and not belong to another employed tag team where the tag team is bookable
        // OR the tag team has a future employment
        // or have a future employment (scope called futureEmployment)
        // or has not been employed (scope called unemployed)

        return Wrestler::query()
            ->where(function ($query) {
                $query->unemployed();
            })
            ->orWhere(function ($query) {
                $query->futureEmployed();
            })
            ->orWhere(function ($query) {
                $query->employed()
                    ->where('status', WrestlerStatus::BOOKABLE)
                    ->whereDoesntHave('currentTagTeam');
            });
    }

    /**
     * Undocumented function
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @return \App\Builders\WrestlerQueryBuilder
     */
    public static function getAvailableWrestlersForExistingTagTeam($tagTeam)
    {
        // Each wrestler must be either:
        // have a currentEmployment (scope called employed)
        // AND have a status of bookable and not belong to another employed tag team where the tag team is bookable
        // OR the tag team has a future employment
        // or have a future employment (scope called futureEmployment)
        // or has not been employed (scope called unemployed)
        // or is currently on the tag team

        return Wrestler::query()
            ->where(function ($query) {
                $query->unemployed();
            })
            ->orWhere(function ($query) {
                $query->futureEmployed();
            })
            ->orWhere(function ($query) {
                $query->employed()
                    ->where('status', WrestlerStatus::BOOKABLE)
                    ->whereDoesntHave('currentTagTeam');
            })
            ->orWhere(function ($query) use ($tagTeam) {
                $query->where('current_tag_team_id', $tagTeam->id);
            });
    }
}
