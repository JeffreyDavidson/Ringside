<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Builders\WrestlerQueryBuilder;
use App\Data\WrestlerData;
use App\Enums\WrestlerStatus;
use App\Exceptions\WrestlerNotOnCurrentTagTeamException;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class WrestlerRepository
{
    /**
     * Create a new wrestler with the given data.
     */
    public function create(WrestlerData $wrestlerData): Model
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
     */
    public function update(Wrestler $wrestler, WrestlerData $wrestlerData): Wrestler
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
     */
    public function delete(Wrestler $wrestler): void
    {
        $wrestler->delete();
    }

    /**
     * Restore a given wrestler.
     */
    public function restore(Wrestler $wrestler): void
    {
        $wrestler->restore();
    }

    /**
     * Employ a given wrestler on a given date.
     */
    public function employ(Wrestler $wrestler, Carbon $employmentDate): Wrestler
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
     */
    public function release(Wrestler $wrestler, Carbon $releaseDate): Wrestler
    {
        $wrestler->currentEmployment()->update(['ended_at' => $releaseDate->toDateTimeString()]);
        $wrestler->save();

        return $wrestler;
    }

    /**
     * Injure a given wrestler on a given date.
     */
    public function injure(Wrestler $wrestler, Carbon $injureDate): Wrestler
    {
        $wrestler->injuries()->create(['started_at' => $injureDate->toDateTimeString()]);
        $wrestler->save();

        return $wrestler;
    }

    /**
     * Clear the injury of a given wrestler on a given date.
     */
    public function clearInjury(Wrestler $wrestler, Carbon $recoveryDate): Wrestler
    {
        $wrestler->currentInjury()->update(['ended_at' => $recoveryDate->toDateTimeString()]);
        $wrestler->save();

        return $wrestler;
    }

    /**
     * Retire a given wrestler on a given date.
     */
    public function retire(Wrestler $wrestler, Carbon $retirementDate): Wrestler
    {
        $wrestler->retirements()->create(['started_at' => $retirementDate->toDateTimeString()]);
        $wrestler->save();

        return $wrestler;
    }

    /**
     * Unretire a given wrestler on a given date.
     */
    public function unretire(Wrestler $wrestler, Carbon $unretireDate): Wrestler
    {
        $wrestler->currentRetirement()->update(['ended_at' => $unretireDate->toDateTimeString()]);
        $wrestler->save();

        return $wrestler;
    }

    /**
     * Suspend a given wrestler on a given date.
     */
    public function suspend(Wrestler $wrestler, Carbon $suspensionDate): Wrestler
    {
        $wrestler->suspensions()->create(['started_at' => $suspensionDate->toDateTimeString()]);
        $wrestler->save();

        return $wrestler;
    }

    /**
     * Reinstate a given wrestler on a given date.
     */
    public function reinstate(Wrestler $wrestler, Carbon $reinstateDate): Wrestler
    {
        $wrestler->currentSuspension()->update(['ended_at' => $reinstateDate->toDateTimeString()]);
        $wrestler->save();

        return $wrestler;
    }

    /**
     * Remove the given wrestler from their current tag team on a given date.
     */
    public function removeFromCurrentTagTeam(Wrestler $wrestler, Carbon $removalDate): void
    {
        // TODO: Remove check from repository
        // if ($wrestler->currentTagTeam === null) {
        //     throw new WrestlerNotOnCurrentTagTeamException();
        // }

        $wrestler->tagTeams()->wherePivotNull('left_at')->updateExistingPivot($wrestler->currentTagTeam->id, [
            'left_at' => $removalDate->toDateTimeString(),
        ]);

        $wrestler->update(['current_tag_team_id' => null]);
    }

    /**
     * Undocumented function.
     */
    public static function getAvailableWrestlersForNewTagTeam(): Collection
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
            })
            ->get();
    }

    /**
     * Undocumented function.
     */
    public static function getAvailableWrestlersForExistingTagTeam(TagTeam $tagTeam): Collection
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
            })
            ->get();
    }
}
