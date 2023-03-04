<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\TagTeamData;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TagTeamRepository
{
    /**
     * Create a new tag team with the given data.
     */
    public function create(TagTeamData $tagTeamData): Model
    {
        return TagTeam::create([
            'name' => $tagTeamData->name,
            'signature_move' => $tagTeamData->signature_move,
        ]);
    }

    /**
     * Update a given tag team with the given data.
     */
    public function update(TagTeam $tagTeam, TagTeamData $tagTeamData): TagTeam
    {
        $tagTeam->update([
            'name' => $tagTeamData->name,
            'signature_move' => $tagTeamData->signature_move,
        ]);

        return $tagTeam;
    }

    /**
     * Delete a given tag team.
     */
    public function delete(TagTeam $tagTeam): void
    {
        $tagTeam->delete();
    }

    /**
     * Restore a given tag team.
     */
    public function restore(TagTeam $tagTeam): void
    {
        $tagTeam->restore();
    }

    /**
     * Employ a given tag team on a given date.
     */
    public function employ(TagTeam $tagTeam, Carbon $employmentDate): TagTeam
    {
        $tagTeam->employments()->updateOrCreate(
            ['ended_at' => null],
            ['started_at' => $employmentDate->toDateTimeString()]
        );
        $tagTeam->save();

        return $tagTeam;
    }

    /**
     * Release a given tag team on a given date.
     */
    public function release(TagTeam $tagTeam, Carbon $releaseDate): TagTeam
    {
        $tagTeam->currentEmployment()->update(['ended_at' => $releaseDate->toDateTimeString()]);
        $tagTeam->save();

        return $tagTeam;
    }

    /**
     * Retire a given tag team on a given date.
     */
    public function retire(TagTeam $tagTeam, Carbon $retirementDate): TagTeam
    {
        $tagTeam->retirements()->create(['started_at' => $retirementDate->toDateTimeString()]);
        $tagTeam->save();

        return $tagTeam;
    }

    /**
     * Unretire a given tag team on a given date.
     */
    public function unretire(TagTeam $tagTeam, Carbon $unretireDate): TagTeam
    {
        $tagTeam->currentRetirement()->update(['ended_at' => $unretireDate->toDateTimeString()]);

        return $tagTeam;
    }

    /**
     * Suspend a given tag team on a given date.
     */
    public function suspend(TagTeam $tagTeam, Carbon $suspensionDate): TagTeam
    {
        $tagTeam->suspensions()->create(['started_at' => $suspensionDate->toDateTimeString()]);
        $tagTeam->save();

        return $tagTeam;
    }

    /**
     * Reinstate a given tag team on a given date.
     */
    public function reinstate(TagTeam $tagTeam, Carbon $reinstateDate): TagTeam
    {
        $tagTeam->currentSuspension()->update(['ended_at' => $reinstateDate->toDateTimeString()]);
        $tagTeam->save();

        return $tagTeam;
    }

    /**
     * Get the model's first employment date.
     */
    public function updateEmployment(TagTeam $tagTeam, Carbon $employmentDate): TagTeam
    {
        $tagTeam->futureEmployment()->update(['started_at' => $employmentDate->toDateTimeString()]);

        return $tagTeam;
    }

    /**
     * Add wrestlers to a tag team.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wrestler>  $wrestlers
     */
    public function addWrestlers(TagTeam $tagTeam, Collection $wrestlers, ?Carbon $joinDate = null): TagTeam
    {
        $joinDate ??= now();

        $wrestlers->each(
            fn (Wrestler $wrestler) => $this->addTagTeamPartner($tagTeam, $wrestler, $joinDate)
        );

        return $tagTeam;
    }

    /**
     * Add wrestlers to a tag team.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wrestler>  $formerTagTeamPartners
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wrestler>  $newTagTeamPartners
     */
    public function syncTagTeamPartners(
        TagTeam $tagTeam,
        Collection $formerTagTeamPartners,
        Collection $newTagTeamPartners,
        ?Carbon $date = null
    ): TagTeam {
        $date ??= now();

        $formerTagTeamPartners->each(
            fn (Wrestler $formerTagTeamPartner) => $this->removeTagTeamPartner(
                $tagTeam,
                $formerTagTeamPartner,
                $date
            )
        );

        $newTagTeamPartners->each(
            fn (Wrestler $newTagTeamPartner) => $this->addTagTeamPartner(
                $tagTeam,
                $newTagTeamPartner,
                $date
            )
        );

        return $tagTeam;
    }

    /**
     * Remove wrestler from a tag team.
     */
    public function removeTagTeamPartner(TagTeam $tagTeam, Wrestler $tagTeamPartner, ?Carbon $removalDate = null): void
    {
        $removalDate ??= now();

        $tagTeam->wrestlers()->wherePivotNull('left_at')->updateExistingPivot(
            $tagTeamPartner->id,
            ['left_at' => $removalDate->toDateTimeString()]
        );
    }

    /**
     * Add wrestler to a tag team.
     */
    public function addTagTeamPartner(TagTeam $tagTeam, Wrestler $tagTeamPartner, ?Carbon $joinDate = null): void
    {
        $joinDate ??= now();

        $tagTeam->wrestlers()->attach(
            $tagTeamPartner->id,
            ['joined_at' => $joinDate->toDateTimeString()]
        );
    }
}
