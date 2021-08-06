<?php

namespace App\Repositories;

use App\Models\Stable;

class StableRepository
{
    /**
     * Create a new stable with the given data.
     *
     * @param  array $data
     * @return \App\Models\Stable
     */
    public function create(array $data)
    {
        return Stable::create([
            'name' => $data['name']
        ]);
    }

    /**
     * Update the given stable with the given data.
     *
     * @param  \App\Models\Stable $stable
     * @param  array $data
     * @return \App\Models\Stable $stable
     */
    public function update(Stable $stable, array $data)
    {
        return $stable->update([
            'name' => $data['name']
        ]);
    }
}
