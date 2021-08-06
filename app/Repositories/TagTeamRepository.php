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
            'signature_move' => $data['signature_move']
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
            'signature_move' => $data['signature_move']
        ]);
    }
}
