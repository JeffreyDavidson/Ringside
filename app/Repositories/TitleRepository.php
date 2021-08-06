<?php

namespace App\Repositories;

use App\Models\Contracts\Activatable;
use App\Models\Contracts\Deactivatable;
use App\Models\Title;
use App\Repositories\Contracts\ActivationRepositoryInterface;
use App\Repositories\Contracts\DeactivationRepositoryInterface;

class TitleRepository implements ActivationRepositoryInterface, DeactivationRepositoryInterface
{
    /**
     * Create a new title with the given data.
     *
     * @param  array $data
     * @return \App\Models\Title
     */
    public function create(array $data)
    {
        return Title::create($data);
    }

    /**
     * Update the given title with the given data.
     *
     * @param  \App\Models\Title $title
     * @param  array $data
     * @return \App\Models\Title $title
     */
    public function update(Title $title, array $data)
    {
        return $title->update([
            'name' => $data['name'],
        ]);
    }

    /**
     * Delete a given title.
     *
     * @param  \App\Models\Title $title
     * @return void
     */
    public function delete(Title $title)
    {
        $title->delete();
    }

    /**
     * Restore a given title.
     *
     * @param  \App\Models\Title $title
     * @return void
     */
    public function restore(Title $title)
    {
        $title->restore();
    }

    /**
     * Activate a given title with a given date.
     *
     * @param  \App\Models\Contracts\Activatable $title
     * @param  string|null $startedAt
     * @return \App\Models\Title $title
     */
    public function activate(Activatable $title, string $startedAt = null)
    {
        return $title->activations()->updateOrCreate(['ended_at' => null], ['started_at' => $startedAt ?? now()]);
    }

    /**
     * Deactivate a given title with a given date.
     *
     * @param  \App\Models\Contracts\Deactivatable $title
     * @param  string|null $endedAt
     * @return \App\Models\Title $title
     */
    public function deactivate(Deactivatable $title, string $endedAt = null)
    {
        return $title->currentActivation()->update(['ended_at' => $endedAt]);
    }
}
