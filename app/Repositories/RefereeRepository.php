<?php

namespace App\Repositories;

use App\Models\Referee;

class RefereeRepository
{
    /**
     * Create a new referee with the given data.
     *
     * @param  array $data
     * @return \App\Models\Referee
     */
    public function create(array $data)
    {
        return Referee::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
        ]);
    }

    /**
     * Update a given referee with the given data.
     *
     * @param  \App\Models\Referee $referee
     * @param  array $data
     * @return \App\Models\Referee $referee
     */
    public function update(Referee $referee, array $data)
    {
        return $referee->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
        ]);
    }

    /**
     * Delete a given referee.
     *
     * @param  \App\Models\Referee $referee
     * @return void
     */
    public function delete(Referee $referee)
    {
        $referee->delete();
    }

    /**
     * Restore a given referee.
     *
     * @param  \App\Models\Referee $referee
     * @return void
     */
    public function restore(Referee $referee)
    {
        $referee->restore();
    }
}
