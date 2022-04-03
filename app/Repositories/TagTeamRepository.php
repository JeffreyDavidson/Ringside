<?php

namespace App\Repositories;

use App\DataTransferObjects\TagTeamData;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class TagTeamRepository
{
    /**
     * Create a new tag team with the given data.
     *
     * @param  \App\DataTransferObjects\TagTeamData $tagTeamData
     * @return \App\Models\TagTeam
     */
    public function create(TagTeamData $tagTeamData)
    {
        return TagTeam::create([
            'name' => $tagTeamData->name,
            'signature_move' => $tagTeamData->signature_move,
        ]);
    }

    /**
     * Update a given tag team with the given data.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  \App\DataTransferObjects\TagTeamData $tagTeamData
     * @return \App\Models\TagTeam
     */
    public function update(TagTeam $tagTeam, TagTeamData $tagTeamData)
    {
        $tagTeam->update([
            'name' => $tagTeamData->name,
            'signature_move' => $tagTeamData->signature_move,
        ]);

        return $tagTeam;
    }

    /**
     * Delete a given tag team.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @return void
     */
    public function delete(TagTeam $tagTeam)
    {
        $tagTeam->delete();
    }

    /**
     * Restore a given tag team.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @return void
     */
    public function restore(TagTeam $tagTeam)
    {
        $tagTeam->restore();
    }

    /**
     * Employ a given tag team on a given date.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  \Carbon\Carbon $employmentDate
     * @return \App\Models\TagTeam
     */
    public function employ(TagTeam $tagTeam, Carbon $employmentDate)
    {
        $tagTeam->employments()->updateOrCreate(
            ['ended_at' => null],
            ['started_at' => $employmentDate->toDateTimeString()]
        );

        return $tagTeam;
    }

    /**
     * Release a given tag team on a given date.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  \Carbon\Carbon $releaseDate
     * @return \App\Models\TagTeam
     */
    public function release(TagTeam $tagTeam, Carbon $releaseDate)
    {
        $tagTeam->currentEmployment()->update(['ended_at' => $releaseDate->toDateTimeString()]);

        return $tagTeam;
    }

    /**
     * Retire a given tag team on a given date.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  \Carbon\Carbon $retirementDate
     * @return \App\Models\TagTeam
     */
    public function retire(TagTeam $tagTeam, Carbon $retirementDate)
    {
        $tagTeam->retirements()->create(['started_at' => $retirementDate->toDateTimeString()]);

        return $tagTeam;
    }

    /**
     * Unretire a given tag team on a given date.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  \Carbon\Carbon $unretireDate
     * @return \App\Models\TagTeam
     */
    public function unretire(TagTeam $tagTeam, Carbon $unretireDate)
    {
        $tagTeam->currentRetirement()->update(['ended_at' => $unretireDate->toDateTimeString()]);

        return $tagTeam;
    }

    /**
     * Suspend a given tag team on a given date.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  \Carbon\Carbon $suspensionDate
     * @return \App\Models\TagTeam
     */
    public function suspend(TagTeam $tagTeam, Carbon $suspensionDate)
    {
        $tagTeam->suspensions()->create(['started_at' => $suspensionDate->toDateTimeString()]);

        return $tagTeam;
    }

    /**
     * Reinstate a given tag team on a given date.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  \Carbon\Carbon $reinstateDate
     * @return \App\Models\TagTeam
     */
    public function reinstate(TagTeam $tagTeam, Carbon $reinstateDate)
    {
        $tagTeam->currentSuspension()->update(['ended_at' => $reinstateDate->toDateTimeString()]);

        return $tagTeam;
    }

    /**
     * Get the model's first employment date.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  \Carbon\Carbon $employmentDate
     * @return \App\Models\TagTeam
     */
    public function updateEmployment(TagTeam $tagTeam, Carbon $employmentDate)
    {
        $tagTeam->futureEmployment()->update(['started_at' => $employmentDate->toDateTimeString()]);

        return $tagTeam;
    }

    /**
     * Add wrestlers to a tag team.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  \Illuminate\Database\Eloquent\Collection $wrestlers
     * @param  \Carbon\Carbon|null $joinDate
     * @return \App\Models\TagTeam
     */
    public function addWrestlers(TagTeam $tagTeam, Collection $wrestlers, ?Carbon $joinDate = null)
    {
        $joinDate ??= now();

        $wrestlers->each(
            fn (Wrestler $wrestler) => $this->addTagTeamPartner($tagTeam, $wrestler->id, $joinDate)
        );

        return $tagTeam;
    }

    /**
     * Add wrestlers to a tag team.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  \Illuminate\Database\Eloquent\Collection $formerTagTeamPartners
     * @param  \Illuminate\Database\Eloquent\Collection $newTagTeamPartners
     * @param  \Carbon\Carbon|null $date
     * @return \App\Models\TagTeam
     */
    public function syncTagTeamPartners(
        TagTeam $tagTeam,
        Collection $formerTagTeamPartners,
        Collection $newTagTeamPartners,
        ?Carbon $date = null
    ) {
        $date ??= now();

        $formerTagTeamPartners->each(
            fn (Wrestler $formerTagTeamPartner) => $this->removeTagTeamPartner(
                $tagTeam,
                $formerTagTeamPartner->id,
                $date
            )
        );

        $newTagTeamPartners->each(
            fn (Wrestler $newTagTeamPartner) => $this->addTagTeamPartner(
                $tagTeam,
                $newTagTeamPartner->id,
                $date
            )
        );

        return $tagTeam;
    }

    /**
     * Remove wrestler from a tag team.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  int $tagTeamPartnerId
     * @param  \Carbon\Carbon $date
     * @return void
     */
    public function removeTagTeamPartner(TagTeam $tagTeam, int $tagTeamPartnerId, Carbon $date)
    {
        $tagTeam->currentWrestlers()->updateExistingPivot(
            $tagTeamPartnerId,
            ['left_at' => $date->toDateTimeString()]
        );
    }

    /**
     * Add wrestler to a tag team.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  int $tagTeamPartnerId
     * @param  \Carbon\Carbon $date
     * @return void
     */
    public function addTagTeamPartner(TagTeam $tagTeam, int $tagTeamPartnerId, Carbon $date)
    {
        $tagTeam->currentWrestlers()->attach(
            $tagTeamPartnerId,
            ['joined_at' => $date->toDateTimeString()]
        );
    }
}
