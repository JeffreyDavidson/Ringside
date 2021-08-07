<?php

namespace App\Repositories;

use App\Models\TagTeam;

class TagTeamRepository
{
    /**
     * Create a new tag team with the given data.
     *
     * @param  array $data
     * @return \App\Models\TagTeam
     */
    public function create(array $data)
    {
        return TagTeam::create([
            'name' => $data['name'],
            'signature_move' => $data['signature_move'],
        ]);
    }

    /**
     * Update a given tag team with the given data.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  array $data
     * @return \App\Models\TagTeam $tagTeam
     */
    public function update(TagTeam $tagTeam, array $data)
    {
        return $tagTeam->update([
            'name' => $data['name'],
            'signature_move' => $data['signature_move'],
        ]);
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
     * @param  string $employmentDate
     * @return \App\Models\TagTeam $tagTeam
     */
    public function employ(TagTeam $tagTeam, string $employmentDate)
    {
        return $tagTeam->employments()->updateOrCreate(['ended_at' => null], ['started_at' => $employmentDate]);
    }

    /**
     * Release a given tag team on a given date.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  string $releaseDate
     * @return \App\Models\TagTeam $tagTeam
     */
    public function release(TagTeam $tagTeam, string $releaseDate)
    {
        return $tagTeam->currentEmployment()->update(['ended_at' => $releaseDate]);
    }

    /**
     * Retire a given tag team on a given date.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  string $retirementDate
     * @return \App\Models\TagTeam $tagTeam
     */
    public function retire(TagTeam $tagTeam, string $retirementDate)
    {
        return $tagTeam->retirements()->create(['started_at' => $retirementDate]);
    }

    /**
     * Unretire a given tag team on a given date.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  string $unretiredDate
     * @return \App\Models\TagTeam $tagTeam
     */
    public function unretire(TagTeam $tagTeam, string $unretiredDate)
    {
        return $tagTeam->currentRetirement()->update(['ended_at' => $unretiredDate]);
    }

    /**
     * Suspend a given tag team on a given date.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  string $suspensionDate
     * @return App\Models\TagTeam $tagTeam
     */
    public function suspend(TagTeam $tagTeam, string $suspensionDate)
    {
        return $tagTeam->suspensions()->create(['started_at' => $suspensionDate]);
    }

    /**
     * Reinstate a given tag team on a given date.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  string $reinstateDate
     * @return \App\Models\TagTeam $tagTeam
     */
    public function reinstate(TagTeam $tagTeam, string $reinstateDate)
    {
        return $tagTeam->currentSuspension()->update(['ended_at' => $reinstateDate]);
    }
}
