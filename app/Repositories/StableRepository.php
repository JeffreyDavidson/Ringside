<?php

namespace App\Repositories;

use App\Models\Contracts\Activatable;
use App\Models\Contracts\Deactivatable;
use App\Models\Stable;
use App\Repositories\Contracts\ActivationRepositoryInterface;
use App\Repositories\Contracts\DeactivationRepositoryInterface;

class StableRepository implements ActivationRepositoryInterface, DeactivationRepositoryInterface
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
            'name' => $data['name'],
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
            'name' => $data['name'],
        ]);
    }

    /**
     * Delete a stable.
     *
     * @param  \App\Models\Stable $stable
     * @return void
     */
    public function delete(Stable $stable)
    {
        $stable->delete();
    }

    /**
     * Restore a stable.
     *
     * @param  \App\Models\Stable $stable
     * @return void
     */
    public function restore(Stable $stable)
    {
        $stable->restore();
    }

    /**
     * Activate a given title with a given date.
     *
     * @param  \App\Models\Contracts\Activatable $stable
     * @param  string|null $startedAt
     * @return \App\Models\Stable $stable
     */
    public function activate(Activatable $stable, string $startedAt = null)
    {
        return $stable->activations()->updateOrCreate(['ended_at' => null], ['started_at' => $startedAt ?? now()]);
    }

    /**
     * Deactivate a given title with a given date.
     *
     * @param  \App\Models\Contracts\Deactivatable $stable
     * @param  string|null $endedAt
     * @return \App\Models\Stable $stable
     */
    public function deactivate(Deactivatable $stable, string $endedAt = null)
    {
        return $stable->currentActivation()->update(['ended_at' => $endedAt]);
    }
}
