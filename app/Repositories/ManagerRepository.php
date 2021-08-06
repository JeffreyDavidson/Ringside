<?php

namespace App\Repositories;

use App\Models\Manager;

class ManagerRepository
{
    /**
     * Create a new manager with the given data.
     *
     * @param  array $data
     * @return \App\Models\Manager
     */
    public function create(array $data)
    {
        return Manager::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
        ]);
    }

    /**
     * Update a given manager with the given data.
     *
     * @param  \App\Models\Manager $manager
     * @param  array $data
     * @return \App\Models\Manager $manager
     */
    public function update(Manager $manager, array $data)
    {
        return $manager->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
        ]);
    }

    /**
     * Delete a given manager.
     *
     * @param  \App\Models\Manager $manager
     * @return void
     */
    public function delete(Manager $manager)
    {
        $manager->delete();
    }

    /**
     * Restore a given manager.
     *
     * @param  \App\Models\Manager $manager
     * @return void
     */
    public function restore(Manager $manager)
    {
        $manager->restore();
    }

    /**
     * Clear the current injury a given manager.
     *
     * @param  \App\Models\Manager $manager
     * @param  string $recoveryDate
     * @return void
     */
    public function clearInjury(Manager $manager, string $recoveryDate)
    {
        $manager->currentInjury()->update(['ended_at' => $recoveryDate]);
    }
}
