<?php

namespace App\Repositories;

use App\Models\Stable;

class StableRepository
{
    /**
     * Undocumented function
     *
     * @param  array $data
     * @return \App\Models\Manager
     */
    public function create(array $data)
    {
        return Stable::create(['name' => $data['name']]);
    }
}
