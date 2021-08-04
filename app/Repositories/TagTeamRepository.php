<?php

namespace App\Repositories;

use App\Models\TagTeam;

class TagTeamRepository
{
    /**
     * Undocumented function
     *
     * @param  array $data
     * @return \App\Models\Manager
     */
    public function create(array $data)
    {
        return TagTeam::create([
            'name' => $data['name'],
            'signature_move' => $data['signature_move']
        ]);
    }
}
